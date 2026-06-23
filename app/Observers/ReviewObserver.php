<?php

namespace App\Observers;

use App\Models\Profile;
use App\Models\Review;

/**
 * Recomputes the target's average rating and review count whenever a review
 * is created. Mirrors the Supabase `recalc_profile_rating` trigger.
 */
class ReviewObserver
{
    public function created(Review $review): void
    {
        $this->recalculate($review->target_id);
    }

    public function deleted(Review $review): void
    {
        $this->recalculate($review->target_id);
    }

    protected function recalculate(string $targetId): void
    {
        $aggregate = Review::where('target_id', $targetId)
            ->selectRaw('ROUND(AVG(rating), 2) as avg_rating, COUNT(*) as total')
            ->first();

        Profile::where('id', $targetId)->update([
            'avg_rating' => $aggregate->avg_rating ?? 0,
            'total_reviews' => $aggregate->total ?? 0,
        ]);
    }
}
