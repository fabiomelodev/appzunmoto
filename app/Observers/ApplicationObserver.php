<?php

namespace App\Observers;

use App\Models\Application;
use App\Models\Notification;
use App\Models\UserSetting;

/**
 * Notifies the shift creator when a courier applies.
 * Mirrors the Supabase `notify_new_candidatura` trigger.
 */
class ApplicationObserver
{
    public function created(Application $application): void
    {
        $shift = $application->shift;

        if (! $shift || $shift->creator_id === $application->user_id) {
            return;
        }

        $settings = UserSetting::find($shift->creator_id);
        if ($settings && ! $settings->notify_shifts) {
            return;
        }

        Notification::create([
            'user_id' => $shift->creator_id,
            'type' => 'vaga',
            'title' => 'Novo candidato na sua vaga',
            'description' => 'Alguém demonstrou interesse em "'.$shift->venue.'"',
            'payload' => ['shift_id' => $shift->id, 'user_id' => $application->user_id],
        ]);
    }
}
