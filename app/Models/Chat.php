<?php
// app/Models/Chat.php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Chat extends Model
{
    use HasUuids;

    protected $fillable = ['vaga_id','user_a','user_b'];

    public function vaga(): BelongsTo    { return $this->belongsTo(Vaga::class, 'vaga_id'); }
    public function mensagens(): HasMany { return $this->hasMany(Message::class, 'chat_id')->orderBy('created_at'); }

    public function outroParticipante(string $userId): string
    {
        return $this->user_a === $userId ? $this->user_b : $this->user_a;
    }
}
