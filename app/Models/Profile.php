<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'has_bag' => 'boolean',
        'avg_rating' => 'float',
        'total_reviews' => 'integer',
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
}
