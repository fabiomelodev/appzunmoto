<?php

namespace App\Console\Commands;

use App\Models\Review;
use Illuminate\Console\Command;

class PublishReviews extends Command
{
    protected $signature = 'reviews:publish';

    protected $description = 'Make reviews public once their waiting period has passed';

    public function handle(): int
    {
        $due = Review::whereNull('published_at')
            ->where('created_at', '<=', now()->subDays(Review::PUBLISH_DELAY_DAYS))
            ->get();

        foreach ($due as $review) {
            // One model at a time on purpose: ReviewObserver refreshes the
            // target's rating from each update. Stamped with the moment it was
            // due, not "now", so the order of reviews never depends on cron timing.
            $review->update(['published_at' => $review->created_at->copy()->addDays(Review::PUBLISH_DELAY_DAYS)]);
        }

        $this->info("Reviews published: {$due->count()}");

        return self::SUCCESS;
    }
}
