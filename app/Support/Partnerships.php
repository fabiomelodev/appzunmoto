<?php

namespace App\Support;

use App\Models\Application;
use App\Models\Chat;
use App\Models\Notification;
use App\Models\Shift;
use App\Models\UserSetting;
use Illuminate\Support\Collection;

/**
 * Business rules for the interest → accept → confirm-partnership → filled flow.
 * Ported from the React `db.ts` (aceitar / recusar / confirmarParceria).
 */
class Partnerships
{
    /** Title of the "you were accepted" notification — the observer pushes it, the toast offers "Confirmar parceria". */
    public const ACCEPTED_TITLE = 'Você foi aceito em uma vaga!';

    /** Creator accepts a courier. Returns the chat between them, or null if the shift is full. */
    public static function accept(Shift $shift, string $courierId): ?Chat
    {
        $apps = $shift->applications()->get();
        $courierApp = $apps->firstWhere('user_id', $courierId);
        $acceptedCount = $apps->where('status', Application::STATUS_ACCEPTED)->count();

        if ($courierApp && $courierApp->status === Application::STATUS_ACCEPTED) {
            return Chat::findOrCreateBetween($shift->id, $shift->creator_id, $courierId);
        }

        $needed = $shift->couriers_needed ?? 1;
        if ($acceptedCount >= $needed) {
            return null;
        }

        $patch = [];
        if (! $shift->reserved_by) {
            $patch['reserved_by'] = $courierId; // legacy: first accepted
        }
        if ($acceptedCount + 1 >= $needed && $shift->status === Shift::STATUS_AVAILABLE) {
            $patch['status'] = Shift::STATUS_RESERVED;
        }
        if ($patch) {
            $shift->update($patch);
        }

        Application::where('shift_id', $shift->id)
            ->where('user_id', $courierId)
            ->update(['status' => Application::STATUS_ACCEPTED]);

        // Notify the courier we just accepted. Done here (not in an observer)
        // so it fires for EVERY accepted courier — including the 2nd+ on a
        // multi-courier shift, where status/reserved_by no longer change.
        // Mirrors the Supabase `notify_candidatura_aceita` trigger.
        Notification::create([
            'user_id' => $courierId,
            'type' => 'turno',
            'title' => self::ACCEPTED_TITLE,
            'description' => 'Sua candidatura em "'.$shift->venue.'" foi aceita. Confirme sua presença.',
            'payload' => ['shift_id' => $shift->id],
        ]);

        return Chat::findOrCreateBetween($shift->id, $shift->creator_id, $courierId);
    }

    public const PAUSED_TITLE = 'Vaga pausada';
    public const RESUMED_TITLE = 'Vaga reativada';

    /**
     * The owner paused/resumed a shift: tell the couriers whose interest is still
     * pending (accepted ones have a partnership in motion, which pausing doesn't touch).
     * Honours the "new shifts" notification preference.
     */
    public static function notifyPauseChange(Shift $shift): void
    {
        $paused = ! $shift->active;

        $courierIds = $shift->applications()
            ->where('status', Application::STATUS_INTERESTED)
            ->pluck('user_id');
        $muted = UserSetting::whereIn('user_id', $courierIds)->where('notify_shifts', false)->pluck('user_id');

        foreach ($courierIds->diff($muted) as $courierId) {
            Notification::create([
                'user_id' => $courierId,
                'type' => 'turno',
                'title' => $paused ? self::PAUSED_TITLE : self::RESUMED_TITLE,
                'description' => $paused
                    ? 'O estabelecimento pausou a vaga "'.$shift->venue.'". Seu interesse continua registrado.'
                    : 'A vaga "'.$shift->venue.'" voltou a ficar disponível.',
                'payload' => ['shift_id' => $shift->id],
            ]);
        }
    }

    /**
     * Candidates waiting for the creator's answer, per shift (id => count): pending interest
     * on shifts that haven't ended and still have a free slot. Drives the "Parcerias" badges;
     * it clears by itself once the creator accepts / declines or the shift fills up / ends.
     *
     * @return \Illuminate\Support\Collection<string, int>
     */
    public static function pendingCandidatesByShift(string $userId): Collection
    {
        return Shift::where('creator_id', $userId)
            ->whereDate('date', '>=', now('America/Sao_Paulo')->subDay()->toDateString())
            ->withCount([
                'applications as pending_count' => fn ($q) => $q->where('status', Application::STATUS_INTERESTED),
                'applications as accepted_count' => fn ($q) => $q->where('status', Application::STATUS_ACCEPTED),
            ])
            ->get()
            ->filter(fn ($s) => $s->pending_count > 0
                && $s->accepted_count < ($s->couriers_needed ?? 1)
                && ! $s->hasEnded())
            ->mapWithKeys(fn ($s) => [$s->id => (int) $s->pending_count]);
    }

    /** Shifts where the creator already accepted this courier and the courier still has to confirm (not ended yet). */
    public static function awaitingConfirmationCount(string $courierId): int
    {
        return Shift::where('creator_id', '!=', $courierId)
            ->whereDate('date', '>=', now('America/Sao_Paulo')->subDay()->toDateString())
            ->whereHas('applications', fn ($q) => $q->where('user_id', $courierId)
                ->where('status', Application::STATUS_ACCEPTED)->where('confirmed', false))
            ->get()
            ->reject(fn ($s) => $s->hasEnded())
            ->count();
    }

    /** Creator declines (removes) a courier's application. */
    public static function decline(Shift $shift, string $courierId): void
    {
        Application::where('shift_id', $shift->id)->where('user_id', $courierId)->delete();
    }

    /**
     * A participant confirms the partnership. When both sides confirm, the slot
     * is locked; once enough are confirmed the shift becomes "filled".
     * Returns true if the shift is now filled.
     */
    public static function confirm(Shift $shift, string $userId, ?string $courierId = null): bool
    {
        $courierFinal = $courierId ?? self::resolveCourier($shift, $userId);
        if (! $courierFinal) {
            return false;
        }

        $app = Application::where('shift_id', $shift->id)->where('user_id', $courierFinal)->first();
        if (! $app || $app->status !== Application::STATUS_ACCEPTED) {
            return false;
        }

        $confirmations = collect($app->confirmations ?? [])->push($userId)->unique()->values()->all();
        $both = in_array($shift->creator_id, $confirmations, true) && in_array($courierFinal, $confirmations, true);
        $app->update(['confirmations' => $confirmations, 'confirmed' => $both]);

        $needed = $shift->couriers_needed ?? 1;
        $accepted = Application::where('shift_id', $shift->id)->where('status', Application::STATUS_ACCEPTED)->get();
        $confirmedCount = $accepted
            ->filter(fn ($c) => $c->user_id === $courierFinal ? $both : $c->confirmed)
            ->count();
        $filled = $confirmedCount >= $needed;

        if ($filled && $shift->status !== Shift::STATUS_FILLED) {
            $shift->update(['status' => Shift::STATUS_FILLED]);
        }

        if ($both) {
            self::notifyBothConfirmed($shift, $courierFinal);
            self::cancelConflicting($shift, $courierFinal);
        }

        return $filled;
    }

    /**
     * A shift the courier has already confirmed that overlaps this one. A confirmed
     * partnership usually marks its shift as "filled", so filled shifts are NOT excluded.
     */
    public static function confirmedConflict(Shift $shift, string $courierId): ?Shift
    {
        return Shift::where('id', '!=', $shift->id)
            ->whereBetween('date', [$shift->date->copy()->subDay()->toDateString(), $shift->date->copy()->addDay()->toDateString()])
            ->whereHas('applications', fn ($q) => $q->where('user_id', $courierId)
                ->where('status', Application::STATUS_ACCEPTED)->where('confirmed', true))
            ->get()
            ->first(fn ($v) => $shift->overlaps($v));
    }

    protected static function resolveCourier(Shift $shift, string $userId): ?string
    {
        if ($userId !== $shift->creator_id) {
            return $userId;
        }

        $firstAccepted = $shift->applications()
            ->where('status', Application::STATUS_ACCEPTED)
            ->value('user_id');

        return $firstAccepted ?? $shift->reserved_by;
    }

    protected static function notifyBothConfirmed(Shift $shift, string $courierId): void
    {
        Notification::create([
            'user_id' => $shift->creator_id,
            'type' => 'turno',
            'title' => 'Parceria confirmada!',
            'description' => 'Parceria fechada para a vaga "'.$shift->venue.'".',
            'payload' => ['shift_id' => $shift->id],
        ]);
        Notification::create([
            'user_id' => $courierId,
            'type' => 'turno',
            'title' => 'Parceria confirmada!',
            'description' => 'Você confirmou presença em "'.$shift->venue.'".',
            'payload' => ['shift_id' => $shift->id],
        ]);
    }

    /** Cancels the courier's other interested applications that overlap this shift. */
    protected static function cancelConflicting(Shift $shift, string $courierId): void
    {
        $conflicting = Shift::where('id', '!=', $shift->id)
            ->where('status', '!=', Shift::STATUS_FILLED)
            // Neighbouring days too: an overnight shift spills into the next date.
            ->whereBetween('date', [$shift->date->copy()->subDay()->toDateString(), $shift->date->copy()->addDay()->toDateString()])
            ->whereHas('applications', fn ($q) => $q->where('user_id', $courierId)->where('status', Application::STATUS_INTERESTED))
            ->get()
            ->filter(fn ($v) => $shift->overlaps($v));

        foreach ($conflicting as $v) {
            Application::where('shift_id', $v->id)
                ->where('user_id', $courierId)
                ->where('status', Application::STATUS_INTERESTED)
                ->delete();
        }
    }
}
