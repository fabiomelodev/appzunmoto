<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Shift extends Model
{
    use HasUuids;

    public const STATUS_AVAILABLE = 'available';
    public const STATUS_RESERVED = 'reserved';
    public const STATUS_FILLED = 'filled';

    protected $fillable = [
        'creator_id',
        'creator_role',
        'venue',
        'region',
        'address',
        'address_photo_url',
        'postal_code',
        'date',
        'start_time',
        'end_time',
        'daily_rate',
        'delivery_fee',
        'delivery_fee_min',
        'delivery_fee_max',
        'venue_type',
        'expected_volume',
        'benefits',
        'accepted_vehicles',
        'requires_own_bag',
        'notes',
        'status',
        'reserved_by',
        'couriers_needed',
        'lat',
        'lng',
        'active',
        'edited_at',
    ];

    protected $casts = [
        'date' => 'date',
        'benefits' => 'array',
        'accepted_vehicles' => 'array',
        'requires_own_bag' => 'boolean',
        'daily_rate' => 'float',
        'delivery_fee' => 'float',
        'delivery_fee_min' => 'float',
        'delivery_fee_max' => 'float',
        'couriers_needed' => 'integer',
        'lat' => 'float',
        'lng' => 'float',
        'active' => 'boolean',
        'edited_at' => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function reservedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reserved_by');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, 'shift_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'shift_id');
    }

    public function chats(): HasMany
    {
        return $this->hasMany(Chat::class, 'shift_id');
    }

    public function contact(): HasOne
    {
        return $this->hasOne(ShiftContact::class, 'shift_id');
    }
}
