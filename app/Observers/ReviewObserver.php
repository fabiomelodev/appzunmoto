<?php

namespace App\Observers;

use App\Models\Profile;
use App\Models\Review;

/**
 * Recomputes the target's average rating and review count whenever a review
 * changes. Mirrors the Supabase `recalc_profile_rating` trigger, kept per
 * role: ratings received as a courier and as an establishment are separate.
 */
class ReviewObserver
{
    public function created(Review $review): void
    {
        $this->recalculate($review->target_id, $review->target_role);
    }

    public function updated(Review $review): void
    {
        $this->recalculate($review->target_id, $review->target_role);
    }

    public function deleted(Review $review): void
    {
        $this->recalculate($review->target_id, $review->target_role);
    }

    protected function recalculate(string $targetId, string $role): void
    {
        $aggregate = Review::where('target_id', $targetId)
            ->where('target_role', $role)
            ->selectRaw('ROUND(AVG(rating), 2) as avg_rating, COUNT(*) as total')
            ->first();

        $columns = $role === 'business'
            ? ['avg' => 'business_avg_rating', 'total' => 'business_total_reviews']
            : ['avg' => 'avg_rating', 'total' => 'total_reviews'];

        Profile::where('id', $targetId)->update([
            $columns['avg'] => $aggregate->avg_rating ?? 0,
            $columns['total'] => $aggregate->total ?? 0,
        ]);
    }
}
