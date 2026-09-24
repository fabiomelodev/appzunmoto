<?php

namespace App\Models;

use App\Observers\ReviewObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([ReviewObserver::class])]
class Review extends Model
{
    use HasUuids;

    /** Days a review stays private after it was written. */
    public const PUBLISH_DELAY_DAYS = 5;

    public $timestamps = false;

    protected $fillable = [
        'shift_id',
        'author_id',
        'target_id',
        'target_role',
        'rating',
        'comment',
        'created_at',
        'published_at',
    ];

    protected $attributes = [
        'comment' => '',
    ];

    protected $casts = [
        'rating' => 'integer',
        'created_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    /** Only reviews already public: what profiles list and what rating averages count. */
    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_id');
    }
}
