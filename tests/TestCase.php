<?php

namespace Tests;

use App\Models\Review;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Carbon;

abstract class TestCase extends BaseTestCase
{
    /** Jumps past the reviews' private period and runs the publisher, as the scheduler would. */
    protected function publishDueReviews(): void
    {
        Carbon::setTestNow(now()->addDays(Review::PUBLISH_DELAY_DAYS)->addMinute());
        $this->artisan('reviews:publish');
        Carbon::setTestNow();
    }
}
