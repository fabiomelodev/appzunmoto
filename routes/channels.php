<?php

use App\Models\Chat;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

// A chat's messages — only the two participants may listen.
Broadcast::channel('chat.{chatId}', function (User $user, string $chatId) {
    $chat = Chat::find($chatId);

    return $chat && $chat->hasParticipant($user->id);
});

// A user's private stream (notifications, unread badge, list signals) — owner only.
Broadcast::channel('user.{userId}', function (User $user, string $userId) {
    return $user->id === $userId;
});
