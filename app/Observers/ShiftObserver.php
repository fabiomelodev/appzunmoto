<?php

namespace App\Observers;

use App\Models\Notification;
use App\Models\Shift;

/**
 * Notifies a courier when they are accepted (shift becomes reserved).
 * Mirrors the Supabase `notify_motoboy_aceito` trigger.
 */
class ShiftObserver
{
    public function updated(Shift $shift): void
    {
        if ($shift->status !== Shift::STATUS_RESERVED || ! $shift->reserved_by) {
            return;
        }

        if (! $shift->wasChanged('status') && ! $shift->wasChanged('reserved_by')) {
            return;
        }

        Notification::create([
            'user_id' => $shift->reserved_by,
            'type' => 'turno',
            'title' => 'Você foi aceito em uma vaga!',
            'description' => 'Sua candidatura em "'.$shift->venue.'" foi aceita.',
            'payload' => ['shift_id' => $shift->id],
        ]);
    }
}
