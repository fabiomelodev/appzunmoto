<?php

namespace App\Console\Commands;

use App\Models\Application;
use App\Models\Chat;
use App\Models\Document;
use App\Models\Message;
use App\Models\Notification;
use App\Models\Profile;
use App\Models\Review;
use App\Models\Shift;
use App\Models\User;
use App\Models\UserAddress;
use App\Observers\ReviewObserver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Cleans test accounts out of the database: deletes EVERY user except the e-mails you list (and
 * administrators). The database cascades most of what hangs off a user, but not everything — this
 * command also repairs what a plain DELETE leaves behind: the ratings of the users who stay, the
 * status of shifts that lost their accepted courier, notifications pointing at deleted
 * chats/shifts, push devices, sessions and uploaded files.
 *
 * Dry run by default: it only reports what would happen. Nothing is deleted without --force
 * (and a confirmation). Take a database backup first.
 */
class PurgeUsers extends Command
{
    protected $signature = 'users:purge
        {--keep=* : E-mail to keep (repeat the option, or separate with commas)}
        {--keep-file= : Text file with one e-mail to keep per line}
        {--force : Actually delete (without it, only a report is shown)}';

    protected $description = 'Delete every user except the listed e-mails and administrators (dry run unless --force)';

    public function handle(): int
    {
        $keepEmails = $this->keepEmails();
        if ($keepEmails === []) {
            $this->error('Informe quem deve ser mantido com --keep=email ou --keep-file=arquivo. Nada foi feito.');

            return self::FAILURE;
        }

        // A typo in the keep list would delete a real person: every listed e-mail must exist.
        $found = User::whereIn(DB::raw('LOWER(email)'), $keepEmails)->pluck('email')->map(fn ($e) => strtolower($e))->all();
        $missing = array_values(array_diff($keepEmails, $found));
        if ($missing !== []) {
            $this->error('Estes e-mails da lista de mantidos não existem no banco (erro de digitação?): '.implode(', ', $missing));
            $this->line('Nada foi feito.');

            return self::FAILURE;
        }

        $keepers = User::where(fn ($q) => $q->whereIn(DB::raw('LOWER(email)'), $keepEmails)->orWhere('is_admin', true))->pluck('id')->all();
        $victims = User::whereNotIn('id', $keepers)->orderBy('created_at')->get(['id', 'name', 'email', 'created_at']);

        if ($victims->isEmpty()) {
            $this->info('Não há usuários para apagar: todos estão na lista de mantidos (ou são administradores).');

            return self::SUCCESS;
        }

        $victimIds = $victims->pluck('id')->all();

        $this->line('');
        $this->info(count($keepers).' usuário(s) serão mantidos (lista + administradores). '.count($victimIds).' serão apagados:');
        $this->table(['Nome', 'E-mail', 'Cadastrado em'], $victims->take(200)->map(fn ($u) => [$u->name, $u->email, $u->created_at?->format('d/m/Y')])->all());
        if ($victims->count() > 200) {
            $this->line('… e mais '.($victims->count() - 200).'.');
        }

        $this->report($victimIds, $keepers);

        if (! $this->option('force')) {
            $this->line('');
            $this->warn('SIMULAÇÃO: nada foi apagado. Para apagar de verdade, rode de novo com --force (faça um backup do banco antes).');

            return self::SUCCESS;
        }

        if (! $this->confirm('Apagar DEFINITIVAMENTE '.count($victimIds).' usuário(s) e tudo o que é deles? Isso não pode ser desfeito.', false)) {
            $this->line('Cancelado. Nada foi apagado.');

            return self::FAILURE;
        }

        $this->purge($victimIds, $keepers);

        return self::SUCCESS;
    }

    /** @return array<int, string> */
    private function keepEmails(): array
    {
        $emails = collect((array) $this->option('keep'))->flatMap(fn ($v) => explode(',', (string) $v));

        if ($file = $this->option('keep-file')) {
            if (! is_readable($file)) {
                $this->error("Não consegui ler o arquivo {$file}.");

                return [];
            }
            $emails = $emails->merge(preg_split('/\R/', (string) file_get_contents($file)));
        }

        return $emails->map(fn ($e) => strtolower(trim((string) $e)))->filter()->unique()->values()->all();
    }

    /** What will go away, and which of the remaining users will be touched. */
    private function report(array $victimIds, array $keepers): void
    {
        $victimShifts = Shift::whereIn('creator_id', $victimIds)->pluck('id');

        $rows = [
            ['Vagas publicadas por eles', $victimShifts->count()],
            ['Candidaturas (deles ou nas vagas deles)', Application::whereIn('user_id', $victimIds)->orWhereIn('shift_id', $victimShifts)->count()],
            ['Conversas', Chat::whereIn('user_a', $victimIds)->orWhereIn('user_b', $victimIds)->orWhereIn('shift_id', $victimShifts)->count()],
            ['Mensagens enviadas por eles', Message::whereIn('author_id', $victimIds)->count()],
            ['Avaliações dadas ou recebidas por eles', Review::whereIn('author_id', $victimIds)->orWhereIn('target_id', $victimIds)->count()],
            ['Endereços', UserAddress::whereIn('user_id', $victimIds)->count()],
            ['Documentos', Document::whereIn('user_id', $victimIds)->count()],
            ['Notificações', Notification::whereIn('user_id', $victimIds)->count()],
        ];
        $this->line('');
        $this->info('Será apagado junto (em cascata):');
        $this->table(['O quê', 'Quantidade'], $rows);

        $touched = $this->touchedKeepers($victimIds, $keepers);
        $this->info('Efeitos sobre quem fica:');
        $this->table(['O quê', 'Quantidade'], [
            ['Usuários mantidos com notas recalculadas', count($touched['ratings'])],
            ['Vagas de quem fica que perdem um candidato/aceito (status recalculado)', count($touched['shifts'])],
            ['Conversas entre quem fica e quem sai (somem)', $touched['chats']],
            ['Candidaturas de quem fica em vagas de quem sai (somem)', $touched['applications']],
        ]);

        foreach (User::whereIn('id', $touched['users'])->get(['name', 'email']) as $u) {
            $this->line("   • {$u->name} <{$u->email}>");
        }
    }

    /** @return array{ratings: array<int, array{0: string, 1: string}>, shifts: array<int, string>, chats: int, applications: int, users: array<int, string>} */
    private function touchedKeepers(array $victimIds, array $keepers): array
    {
        $ratings = Review::where(fn ($q) => $q->whereIn('author_id', $victimIds)->orWhereIn('target_id', $victimIds))
            ->whereIn('target_id', $keepers)
            ->get(['target_id', 'target_role'])
            ->map(fn ($r) => [$r->target_id, $r->target_role])->unique()->values()->all();

        $shifts = Shift::whereIn('creator_id', $keepers)
            ->whereHas('applications', fn ($q) => $q->whereIn('user_id', $victimIds))
            ->pluck('id')->all();

        $victimShifts = Shift::whereIn('creator_id', $victimIds)->pluck('id');
        $applications = Application::whereIn('user_id', $keepers)->whereIn('shift_id', $victimShifts)->get(['user_id']);

        $chats = Chat::where(fn ($q) => $q->whereIn('user_a', $victimIds)->orWhereIn('user_b', $victimIds))
            ->where(fn ($q) => $q->whereIn('user_a', $keepers)->orWhereIn('user_b', $keepers))->count();

        $users = collect($ratings)->pluck(0)
            ->merge(Shift::whereIn('id', $shifts)->pluck('creator_id'))
            ->merge($applications->pluck('user_id'))->unique()->values()->all();

        return ['ratings' => $ratings, 'shifts' => $shifts, 'chats' => $chats, 'applications' => $applications->count(), 'users' => $users];
    }

    private function purge(array $victimIds, array $keepers): void
    {
        // Gathered BEFORE deleting: afterwards there is nothing left to look at.
        $touched = $this->touchedKeepers($victimIds, $keepers);
        $files = $this->collectFiles($victimIds);

        DB::transaction(function () use ($victimIds, $touched, $keepers) {
            foreach (array_chunk($victimIds, 200) as $chunk) {
                // Query-level delete: the foreign keys cascade profile, shifts, applications, chats, messages,
                // reviews, notifications, addresses, documents, settings.
                User::whereIn('id', $chunk)->delete();

                DB::table('push_subscriptions')->where('subscribable_type', User::class)->whereIn('subscribable_id', $chunk)->delete();
                if (Schema::hasTable('sessions')) {
                    DB::table('sessions')->whereIn('user_id', $chunk)->delete();
                }
            }

            // Ratings are only recalculated by the review observer on create/update/delete — not by cascades.
            $observer = new ReviewObserver;
            foreach ($touched['ratings'] as [$targetId, $role]) {
                $observer->recalculate($targetId, $role);
            }

            foreach (Shift::whereIn('id', $touched['shifts'])->get() as $shift) {
                $this->recalculateShiftStatus($shift);
            }

            $this->deleteDeadNotifications($keepers);
        });

        // Files only after the transaction committed.
        $removed = $this->deleteFiles($files, $victimIds);

        $this->line('');
        $this->info('Pronto. '.count($victimIds).' usuário(s) apagados; '.count($touched['ratings']).' nota(s) e '.count($touched['shifts']).' vaga(s) recalculadas; '.$removed.' arquivo(s) removidos.');
    }

    /** A shift that lost an applicant: put its status back in line with who is still accepted/confirmed. */
    private function recalculateShiftStatus(Shift $shift): void
    {
        $needed = $shift->couriers_needed ?? 1;
        $accepted = Application::where('shift_id', $shift->id)->where('status', Application::STATUS_ACCEPTED)->get();
        $confirmed = $accepted->where('confirmed', true)->count();

        $status = match (true) {
            $confirmed >= $needed => Shift::STATUS_FILLED,
            $accepted->count() >= $needed => Shift::STATUS_RESERVED,
            default => Shift::STATUS_AVAILABLE,
        };

        $reservedBy = $accepted->first()?->user_id;

        $shift->forceFill(['status' => $status, 'reserved_by' => $reservedBy])->saveQuietly();
    }

    /** Notifications of the remaining users that point at a chat or shift that no longer exists. */
    private function deleteDeadNotifications(array $keepers): void
    {
        Notification::whereIn('user_id', $keepers)->orderBy('created_at')->chunkById(200, function ($notifications) {
            $dead = $notifications->filter(function ($n) {
                $payload = $n->payload ?? [];

                return (! empty($payload['chat_id']) && ! Chat::whereKey($payload['chat_id'])->exists())
                    || (! empty($payload['shift_id']) && ! Shift::whereKey($payload['shift_id'])->exists());
            });

            Notification::whereIn('id', $dead->pluck('id'))->delete();
        });
    }

    /** @return array{public: array<int, string>, documents: array<int, string>} */
    private function collectFiles(array $victimIds): array
    {
        $urls = Profile::whereIn('id', $victimIds)->pluck('photo_url')
            ->merge(UserAddress::whereIn('user_id', $victimIds)->pluck('photo_url'))
            ->merge(Shift::whereIn('creator_id', $victimIds)->pluck('address_photo_url'));

        $public = $urls->filter()->map(fn ($u) => str_contains($u, '/storage/') ? substr($u, strpos($u, '/storage/') + 9) : null)
            ->filter()->unique()->values()->all();

        return ['public' => $public, 'documents' => array_map(fn ($id) => 'documents/'.$id, $victimIds)];
    }

    private function deleteFiles(array $files, array $victimIds): int
    {
        $count = 0;
        $public = Storage::disk('public');

        foreach ($files['public'] as $path) {
            // Never remove a file a remaining row still uses (an address photo copied to a kept user's shift).
            $url = '/storage/'.$path;
            $stillUsed = Profile::where('photo_url', 'like', '%'.$url)->exists()
                || UserAddress::where('photo_url', 'like', '%'.$url)->exists()
                || Shift::where('address_photo_url', 'like', '%'.$url)->exists();

            if (! $stillUsed && $public->exists($path)) {
                $public->delete($path);
                $count++;
            }
        }

        foreach ($files['documents'] as $dir) {
            if (Storage::disk('local')->exists($dir)) {
                Storage::disk('local')->deleteDirectory($dir);
                $count++;
            }
        }

        return $count;
    }
}
