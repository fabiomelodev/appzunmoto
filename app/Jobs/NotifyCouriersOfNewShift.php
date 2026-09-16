<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Models\Profile;
use App\Models\Shift;
use App\Models\UserSetting;
use App\Support\Catalog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Fans out a "nova vaga compatível" notification to every courier whose
 * vehicle matches the shift's restriction, excluding the creator. Queued
 * because the recipient count isn't bounded like the other observers.
 */
class NotifyCouriersOfNewShift implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Shift $shift) {}

    public function handle(): void
    {
        $acceptedVehicles = $this->shift->accepted_vehicles ?? [];
        $optedIn = UserSetting::where('notify_shifts', true)->pluck('user_id');

        Profile::query()
            ->where('role', 'courier')
            ->where('id', '!=', $this->shift->creator_id)
            ->whereIn('id', $optedIn)
            ->chunkById(200, function ($couriers) use ($acceptedVehicles) {
                foreach ($couriers as $courier) {
                    if (! Catalog::vehicleCompatible($acceptedVehicles, $courier->vehicle)) {
                        continue;
                    }

                    Notification::create([
                        'user_id' => $courier->id,
                        'type' => 'nova_vaga',
                        'title' => 'Nova vaga compatível',
                        'description' => '"'.$this->shift->venue.'" — '.$this->shift->region,
                        'payload' => ['shift_id' => $this->shift->id],
                    ]);
                }
            });
    }
}
