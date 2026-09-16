<?php

namespace App\Observers;

use App\Jobs\NotifyCouriersOfNewShift;
use App\Models\Shift;

/** Notifies compatible couriers when a new shift is published. */
class ShiftObserver
{
    public function created(Shift $shift): void
    {
        // Columns like `status`/`active` fall back to DB defaults on insert,
        // which aren't reflected on this in-memory instance until refreshed.
        $shift = $shift->fresh();

        if (! $shift || $shift->status !== Shift::STATUS_AVAILABLE || ! $shift->active) {
            return;
        }

        NotifyCouriersOfNewShift::dispatch($shift);
    }
}
