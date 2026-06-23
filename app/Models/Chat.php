<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Chat extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'shift_id',
        'user_a',
        'user_b',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'chat_id')->orderBy('created_at');
    }

    /** Returns the id of the participant that is not $userId. */
    public function otherParticipant(string $userId): string
    {
        return $this->user_a === $userId ? $this->user_b : $this->user_a;
    }

    /**
     * Finds (or creates) the chat between two users for a shift. Participants
     * are stored sorted so the (shift, a, b) unique key is stable either way.
     */
    public static function findOrCreateBetween(string $shiftId, string $first, string $second): self
    {
        $pair = collect([$first, $second])->sort()->values();

        return static::firstOrCreate([
            'shift_id' => $shiftId,
            'user_a' => $pair[0],
            'user_b' => $pair[1],
        ]);
    }

    public function hasParticipant(string $userId): bool
    {
        return $this->user_a === $userId || $this->user_b === $userId;
    }
}
