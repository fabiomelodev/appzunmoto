<?php

namespace App\Models;

use App\Observers\ApplicationObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([ApplicationObserver::class])]
class Application extends Model
{
    use HasUuids;

    public const STATUS_INTERESTED = 'interested';
    public const STATUS_ACCEPTED = 'accepted';

    public $timestamps = false;

    protected $fillable = [
        'shift_id',
        'user_id',
        'status',
        'confirmed',
        'confirmations',
    ];

    protected $attributes = [
        'status' => self::STATUS_INTERESTED,
        'confirmed' => false,
    ];

    protected $casts = [
        'confirmed' => 'boolean',
        'confirmations' => 'array',
        'created_at' => 'datetime',
    ];

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
