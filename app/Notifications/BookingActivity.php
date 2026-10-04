<?php

namespace App\Notifications;

use App\Notifications\Channels\WhatsAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BookingActivity extends Notification
{
    use Queueable;

    /**
     * @param string $event   created | cancelled | rescheduled | approved | rejected | paid | blocked | reopened
     * @param string $title   judul singkat notifikasi
     * @param string $message isi notifikasi
     * @param string $url     halaman tujuan saat notifikasi diklik
     */
    public function __construct(
        public string $event,
        public string $title,
        public string $message,
        public string $url,
        public ?int $bookingId = null,
    ) {
    }

    /**
     * Lonceng (database) selalu. WhatsApp untuk admin jika diaktifkan di Settings.
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (($notifiable->role ?? null) === 'admin' && class_exists(\App\Support\WhatsApp::class)) {
            $channels[] = WhatsAppChannel::class;
        }

        return $channels;
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

    public function toWhatsApp(object $notifiable): array
    {
        return [
            'event'   => $this->event,
            'title'   => $this->title,
            'message' => $this->message,
            'url'     => $this->url,
        ];
    }
}
