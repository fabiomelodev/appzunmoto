<?php

namespace App\Models;

use App\Observers\ShiftObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

#[ObservedBy([ShiftObserver::class])]
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

    /**
     * Real start/end of a shift. Date and times are São Paulo wall-clock values
     * (the app itself runs on UTC), and the date is the day it starts: an end
     * time at or before the start time means it ends the next day (20:00–03:00).
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function window(string $date, string $startTime, string $endTime): array
    {
        $start = Carbon::parse("{$date} {$startTime}", 'America/Sao_Paulo');
        $end = Carbon::parse("{$date} {$endTime}", 'America/Sao_Paulo');

        return [$start, $end->lte($start) ? $end->addDay() : $end];
    }

    /** @return array{0: Carbon, 1: Carbon} */
    public function timeWindow(): array
    {
        return self::window($this->date->toDateString(), $this->start_time, $this->end_time);
    }

    public function startsAt(): Carbon
    {
        return $this->timeWindow()[0];
    }

    public function endsAt(): Carbon
    {
        return $this->timeWindow()[1];
    }

    public function hasEnded(): bool
    {
        return $this->endsAt()->isPast();
    }

    public function crossesMidnight(): bool
    {
        [$start, $end] = $this->timeWindow();

        return ! $start->isSameDay($end);
    }

    /** True when the two time windows share any moment (touching edges don't count). */
    public static function windowsOverlap(array $a, array $b): bool
    {
        return $a[0]->lt($b[1]) && $b[0]->lt($a[1]);
    }

    public function overlaps(self $other): bool
    {
        return self::windowsOverlap($this->timeWindow(), $other->timeWindow());
    }

    /** "18:00–23:00", or "20:00–03:00 (+1 dia)" when it runs past midnight. */
    public function timeRange(string $separator = '–'): string
    {
        return $this->start_time.$separator.$this->end_time.($this->crossesMidnight() ? ' (+1 dia)' : '');
    }

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
