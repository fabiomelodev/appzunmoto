<?php
// app/Models/Message.php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = ['chat_id','autor_id','texto'];

    protected $casts = ['created_at' => 'datetime'];

    public function chat(): BelongsTo  { return $this->belongsTo(Chat::class, 'chat_id'); }
    public function autor(): BelongsTo { return $this->belongsTo(User::class, 'autor_id'); }
}
