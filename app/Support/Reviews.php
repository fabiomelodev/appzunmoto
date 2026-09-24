<?php

namespace App\Support;

use App\Models\Application;
use App\Models\Review;
use App\Models\Shift;
use Illuminate\Support\Collection;

/**
 * Two-way review rules for a finished shift: the creator reviews each courier
 * whose partnership was confirmed, and each of those couriers reviews the
 * creator. Single source of truth for the shift page, the chat and the
 * end-of-shift reminder.
 */
class Reviews
{
    /** @return Collection<int, string> user ids of the couriers with a confirmed partnership */
    public static function confirmedCourierIds(Shift $shift): Collection
    {
        return Application::where('shift_id', $shift->id)
            ->where('status', Application::STATUS_ACCEPTED)
            ->where('confirmed', true)
            ->pluck('user_id');
    }

    public static function canReview(Shift $shift, string $authorId, string $targetId): bool
    {
        if ($authorId === $targetId || ! $shift->hasEnded()) {
            return false;
        }

        $couriers = self::confirmedCourierIds($shift);

        if ($authorId === $shift->creator_id) {
            return $couriers->contains($targetId);
        }

        return $targetId === $shift->creator_id && $couriers->contains($authorId);
    }

    public static function hasReviewed(Shift $shift, string $authorId, string $targetId): bool
    {
        return Review::where('shift_id', $shift->id)
            ->where('author_id', $authorId)
            ->where('target_id', $targetId)
            ->exists();
    }

    /**
     * Everyone $userId may still review on this shift.
     *
     * @return Collection<int, string>
     */
    public static function pendingTargetIds(Shift $shift, string $userId): Collection
    {
        return self::reviewableIds($shift, $userId)
            ->reject(fn (string $id) => self::hasReviewed($shift, $userId, $id))
            ->values();
    }

    /**
     * Everyone $userId can review on this shift once it ends (reviewed or not),
     * ignoring the "has it ended" check so callers can decide timing themselves.
     *
     * @return Collection<int, string>
     */
    public static function reviewableIds(Shift $shift, string $userId): Collection
    {
        $couriers = self::confirmedCourierIds($shift);

        if ($userId === $shift->creator_id) {
            return $couriers->values();
        }

        return $couriers->contains($userId) ? collect([$shift->creator_id]) : collect();
    }

    /** Which role the reviewed person played on this shift. */
    public static function targetRole(Shift $shift, string $targetId): string
    {
        return ($targetId === $shift->creator_id && $shift->creator_role === 'business') ? 'business' : 'courier';
    }

    public static function submit(Shift $shift, string $authorId, string $targetId, int $rating, string $comment): bool
    {
        if ($rating < 1 || $rating > 5
            || ! self::canReview($shift, $authorId, $targetId)
            || self::hasReviewed($shift, $authorId, $targetId)) {
            return false;
        }

        // Starts private; PublishReviews makes it public Review::PUBLISH_DELAY_DAYS later.
        Review::create([
            'shift_id' => $shift->id,
            'author_id' => $authorId,
            'target_id' => $targetId,
            'target_role' => self::targetRole($shift, $targetId),
            'rating' => $rating,
            'comment' => trim($comment),
            'created_at' => now(),
        ]);

        return true;
    }
}
