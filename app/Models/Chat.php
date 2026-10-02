<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

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
        'user_a_read_at' => 'datetime',
        'user_b_read_at' => 'datetime',
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

    /** Same lookup as findOrCreateBetween(), but never creates one. */
    public static function findBetween(string $shiftId, string $first, string $second): ?self
    {
        $pair = collect([$first, $second])->sort()->values();

        return static::where('shift_id', $shiftId)->where('user_a', $pair[0])->where('user_b', $pair[1])->first();
    }

    private function readColumnFor(string $userId): string
    {
        return $this->user_a === $userId ? 'user_a_read_at' : 'user_b_read_at';
    }

    /** Marks everything in this conversation as read by $userId, as of now. */
    public function markReadBy(string $userId): void
    {
        $this->forceFill([$this->readColumnFor($userId) => now()])->saveQuietly();
    }

    /**
     * Unread messages per chat for $userId (messages from the other person written after
     * this user's read marker), keyed by chat id. Chats with nothing unread are omitted.
     *
     * @param  array<int, string>|null  $chatIds  limit to these chats (null = all of the user's)
     * @return Collection<string, int>
     */
    public static function unreadCountsFor(string $userId, ?array $chatIds = null): Collection
    {
        return Message::query()
            ->join('chats', 'chats.id', '=', 'messages.chat_id')
            ->where(fn ($q) => $q->where('chats.user_a', $userId)->orWhere('chats.user_b', $userId))
            ->where('messages.author_id', '!=', $userId)
            ->whereRaw("messages.created_at > COALESCE(CASE WHEN chats.user_a = ? THEN chats.user_a_read_at ELSE chats.user_b_read_at END, '1970-01-01')", [$userId])
            ->when($chatIds !== null, fn ($q) => $q->whereIn('messages.chat_id', $chatIds))
            ->groupBy('messages.chat_id')
            ->selectRaw('messages.chat_id, COUNT(*) as unread')
            ->pluck('unread', 'chat_id');
    }

    public function hasParticipant(string $userId): bool
    {
        return $this->user_a === $userId || $this->user_b === $userId;
    }
}
