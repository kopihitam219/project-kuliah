<?php

namespace App\Notifications\Channels;

use App\Support\WhatsApp;
use Illuminate\Notifications\Notification;

/**
 * Meneruskan notifikasi lonceng admin ke WhatsApp admin.
 */
class WhatsAppChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toWhatsApp')) {
            return;
        }

        $data = $notification->toWhatsApp($notifiable);

        WhatsApp::queueToAdmins($data['event'], $data['title'], $data['message'], $data['url'] ?? null);
    }
}
