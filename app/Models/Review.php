<?php
// app/Models/Review.php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    use HasUuids;

    protected $fillable = ['vaga_id','autor_id','alvo_id','nota','comentario'];

    protected $casts = ['nota' => 'integer'];

    public function vaga(): BelongsTo   { return $this->belongsTo(Vaga::class, 'vaga_id'); }
    public function autor(): BelongsTo  { return $this->belongsTo(User::class, 'autor_id'); }
    public function alvo(): BelongsTo   { return $this->belongsTo(User::class, 'alvo_id'); }
}
