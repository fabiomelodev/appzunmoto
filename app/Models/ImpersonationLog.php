<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImpersonationLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['admin_id', 'user_id', 'ip', 'started_at', 'ended_at'];

    protected $casts = ['started_at' => 'datetime', 'ended_at' => 'datetime'];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
