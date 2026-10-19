<?php

namespace App\Models;

use App\Support\Acquisition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A marketing channel (TV, panfleto, Instagram…). `slug` is the value of ?utm_source=… in the
 * links/QR codes, e.g. https://app.zunmoto.com.br?utm_source=tv.
 */
class AcquisitionChannel extends Model
{
    protected $fillable = ['name', 'slug', 'active'];

    protected $casts = ['active' => 'boolean'];

    /** Slugs are compared lower-cased and stripped of anything a URL parameter shouldn't carry. */
    public function setSlugAttribute(?string $value): void
    {
        $this->attributes['slug'] = Acquisition::normalize((string) $value);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'acquisition_channel_id');
    }

    /** The link to print / put in a QR code for this channel. */
    public function url(): string
    {
        return rtrim((string) config('app.url'), '/').'?utm_source='.$this->slug;
    }
}
