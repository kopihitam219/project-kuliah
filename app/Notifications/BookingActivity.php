<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BookingActivity extends Notification
{
    use Queueable;

    /**
     * @param string   $event     created | cancelled | rescheduled | approved | rejected
     * @param string   $title     judul singkat notifikasi
     * @param string   $message   isi notifikasi
     * @param string   $url       halaman tujuan saat notifikasi diklik
     */
    public function __construct(
        public string $event,
        public string $title,
        public string $message,
        public string $url,
        public ?int $bookingId = null,
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
            'booking_id' => $this->bookingId,
        ];
    }
}
