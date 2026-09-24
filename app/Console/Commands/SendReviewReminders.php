<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\Shift;
use App\Models\UserSetting;
use App\Support\Reviews;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendReviewReminders extends Command
{
    protected $signature = 'reviews:send-reminders';

    protected $description = 'Notify the people of a finished shift (creator and confirmed couriers) that they can now review each other';

    /** Only shifts that ended within this many days are considered, so a first run never floods old history. */
    protected const WINDOW_DAYS = 7;

    public function handle(): int
    {
        $today = Carbon::now('America/Sao_Paulo')->startOfDay();

        $shifts = Shift::whereNull('review_reminder_sent_at')
            ->whereDate('date', '<=', $today->toDateString())
            ->whereDate('date', '>=', $today->copy()->subDays(self::WINDOW_DAYS)->toDateString())
            ->get()
            ->filter(fn (Shift $shift) => $shift->hasEnded());

        $sent = 0;
        foreach ($shifts as $shift) {
            $couriers = Reviews::confirmedCourierIds($shift);
            if ($couriers->isEmpty()) {
                continue; // nobody to review (yet) — checked again on the next run, inside the window
            }

            $sent += $this->notifyCreator($shift);
            foreach ($couriers as $courierId) {
                $sent += $this->notifyCourier($shift, $courierId);
            }

            // Query builder on purpose: no ShiftObserver side effects for a bookkeeping column.
            Shift::whereKey($shift->id)->update(['review_reminder_sent_at' => now()]);
        }

        $this->info("Review reminders sent: {$sent}");

        return self::SUCCESS;
    }

    protected function notifyCreator(Shift $shift): int
    {
        $pending = Reviews::pendingTargetIds($shift, $shift->creator_id);
        if ($pending->isEmpty()) {
            return 0;
        }

        return $this->notify(
            $shift->creator_id,
            $shift,
            $pending->count() === 1
                ? 'Avalie o motoboy da vaga "'.$shift->venue.'".'
                : 'Avalie os motoboys da vaga "'.$shift->venue.'".',
        );
    }

    protected function notifyCourier(Shift $shift, string $courierId): int
    {
        if (Reviews::hasReviewed($shift, $courierId, $shift->creator_id)) {
            return 0;
        }

        $who = $shift->creator_role === 'business' ? 'o estabelecimento' : 'quem publicou a vaga';

        return $this->notify($courierId, $shift, 'Avalie '.$who.' da vaga "'.$shift->venue.'".');
    }

    protected function notify(string $userId, Shift $shift, string $description): int
    {
        $settings = UserSetting::find($userId);
        if ($settings && ! $settings->notify_shifts) {
            return 0;
        }

        Notification::create([
            'user_id' => $userId,
            'type' => 'avaliacao',
            'title' => 'Como foi a vaga?',
            'description' => $description,
            'payload' => ['shift_id' => $shift->id],
        ]);

        return 1;
    }
}
