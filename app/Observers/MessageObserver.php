<?php

namespace App\Observers;

use App\Events\MessageSent;
use App\Models\Message;
use App\Models\Notification;
use App\Models\UserSetting;
use Illuminate\Support\Str;

/**
 * Notifies the other chat participant of a new message.
 * Mirrors the Supabase `notify_new_message` trigger.
 */
class MessageObserver
{
    public function created(Message $message): void
    {
        $chat = $message->chat;
        if (! $chat) {
            return;
        }

        // Push the message to the chat's private channel for both open
        // conversations (->toOthers() skips the sender, who already sees it
        // via the Livewire round-trip). Best-effort: never break on a down Reverb.
        try {
            broadcast(new MessageSent($message))->toOthers();
        } catch (\Throwable $e) {
            report($e);
        }

        $recipientId = $chat->otherParticipant($message->author_id);

        $settings = UserSetting::find($recipientId);
        if ($settings && ! $settings->notify_chat) {
            return;
        }

        Notification::create([
            'user_id' => $recipientId,
            'type' => 'mensagem',
            'title' => 'Nova mensagem',
            'description' => Str::limit($message->body, 80, ''),
            'payload' => ['chat_id' => $chat->id],
        ]);
    }
}
