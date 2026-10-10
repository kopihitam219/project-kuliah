<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi lonceng untuk chat & broadcast (hanya database, tanpa WhatsApp).
 * Bentuk datanya sama dengan BookingActivity supaya tampil di lonceng & halaman Notifikasi.
 */
class ChatActivity extends Notification
{
    use Queueable;

    public function __construct(
        public string $event,      // chat | broadcast
        public string $title,
        public string $message,
        public string $url,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'event'      => $this->event,
            'title'      => $this->title,
            'message'    => $this->message,
            'url'        => $this->url,
            'booking_id' => null,
        ];
    }
}
