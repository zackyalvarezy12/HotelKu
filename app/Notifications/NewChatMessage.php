<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewChatMessage extends Notification
{
    use Queueable;

    public function __construct(private readonly Message $message) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $this->message->loadMissing('sender', 'conversation.hotel');

        return [
            'conversation_id' => $this->message->conversation_id,
            'message_id' => $this->message->getKey(),
            'sender_name' => $this->message->sender->name,
            'hotel_name' => $this->message->conversation->hotel->name,
            'body' => str($this->message->body)->limit(120)->toString(),
            'url' => route('chat.inbox', ['conversation' => $this->message->conversation_id]),
        ];
    }
}