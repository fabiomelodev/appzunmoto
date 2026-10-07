<?php

namespace App\Models;

use App\Support\Geocoder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Profile shares its primary key with the owning user (id = users.id).
 */
class Profile extends Model
{
    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'role',
        'onboarded_at',
        'name',
        'photo_url',
        'city',
        'radius_km',
        'base_lat',
        'base_lng',
        'bio',
        'phone',
        'cpf',
        'birth_date',
        'street',
        'street_number',
        'district',
        'has_bag',
        'vehicle',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'onboarded_at' => 'datetime',
        'has_bag' => 'boolean',
        'radius_km' => 'integer',
        'base_lat' => 'float',
        'base_lng' => 'float',
        'avg_rating' => 'float',
        'total_reviews' => 'integer',
        'business_avg_rating' => 'float',
        'business_total_reviews' => 'integer',
    ];

    /** Fields safe to expose to other users (mirrors the Supabase public_profiles view). */
    public const PUBLIC_FIELDS = [
        'id', 'name', 'photo_url', 'city', 'bio', 'district',
        'has_bag', 'vehicle', 'avg_rating', 'total_reviews', 'role',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id');
    }

    /** Select only public-safe columns (no cpf, phone, birth_date, address). */
    public function scopePublicColumns(Builder $query): Builder
    {
        return $query->select(self::PUBLIC_FIELDS);
    }

    public function isCourier(): bool
    {
        return $this->role === 'courier';
    }

    public function isBusiness(): bool
    {
        return $this->role === 'business';
    }

    public function isOnboarded(): bool
    {
        return ! is_null($this->onboarded_at);
    }

    /** Whether the birth date on file makes this account 18+ (required to be "estabelecimento" or pick "moto"). */
    public function isAdult(): bool
    {
        return $this->birth_date && ! Carbon::parse($this->birth_date)->isAfter(now()->subYears(18));
    }

    /**
     * Geocodes the courier's base location (district + city, plus the CEP when known) so the
     * radius filter has a centre. Best-effort: a failed lookup keeps the previous coordinates
     * rather than wiping them. Skipped under tests (no network).
     */
    public function refreshBaseLocation(?string $cep = null): void
    {
        // No network under tests unless a test opts in (config + Http::fake).
        if (blank($this->city) || (app()->runningUnitTests() && ! config('app.geocode_in_tests'))) {
            return;
        }

        $coords = Geocoder::forAddress(null, null, $this->district, $this->city, $cep);
        if ($coords) {
            $this->forceFill(['base_lat' => $coords['lat'], 'base_lng' => $coords['lng']])->save();
        }
    }

    /**
     * Accounts created before the radius existed (or whose geocoding failed) have a district/city
     * but no coordinates, so the radius would silently do nothing. Locate them on first use —
     * at most once every few hours per account, since a failed lookup is slow.
     */
    public function ensureBaseLocation(): void
    {
        if (! $this->isCourier() || $this->base_lat !== null || blank($this->city)) {
            return;
        }

        if (\Illuminate\Support\Facades\Cache::add('base-location-tried:'.$this->id, 1, now()->addHours(6))) {
            $this->refreshBaseLocation();
        }
    }

    /** True for a courier whose radius can't work yet (no located base): drives the "fix your neighbourhood" notices. */
    public function radiusInactive(): bool
    {
        return $this->isCourier() && $this->onboarded_at !== null && ($this->base_lat === null || (float) $this->base_lat === 0.0);
    }

    /** Data still needed to act as courier: "cidade base" (district/city) and a vehicle. */
    public function missingCourierFields(): bool
    {
        return blank($this->district) || blank($this->city) || blank($this->vehicle);
    }
}
