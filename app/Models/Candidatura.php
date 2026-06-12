<?php
// app/Models/Candidatura.php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Candidatura extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = ['vaga_id','user_id','status'];

    protected $attributes = ['status' => 'interessado'];

    public function vaga(): BelongsTo
    {
        return $this->belongsTo(Vaga::class, 'vaga_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
