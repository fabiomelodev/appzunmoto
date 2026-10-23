<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** The ZunMoto PWA installed on one device of one user (see PwaController::installed). */
class AppInstall extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'device_key', 'platform', 'installed_at', 'last_seen_at', 'last_standalone_at'];

    protected $casts = [
        'installed_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'last_standalone_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
