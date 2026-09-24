<?php

namespace App\Models;

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

    /** Data still needed to act as courier: "cidade base" (district/city) and a vehicle. */
    public function missingCourierFields(): bool
    {
        return blank($this->district) || blank($this->city) || blank($this->vehicle);
    }
}
