<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * Halaman semua notifikasi (admin & customer).
     */
    public function index(Request $request): View
    {
        $filter = $request->query('filter') === 'unread' ? 'unread' : 'all';
        $user   = $request->user();

        $notifications = ($filter === 'unread' ? $user->unreadNotifications() : $user->notifications())
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('notifications.index', [
            'notifications' => $notifications,
            'filter'        => $filter,
            'unreadCount'   => $user->unreadNotifications()->count(),
            'totalCount'    => $user->notifications()->count(),
        ]);
    }

    /**
     * Detail satu notifikasi (otomatis ditandai sudah dibaca).
     */
    public function show(Request $request, string $notification): View
    {
        $user = $request->user();
        $item = $user->notifications()->findOrFail($notification);
        $item->markAsRead();

        $booking = null;

        if (! empty($item->data['booking_id'])) {
            $booking = Booking::with('user')->find($item->data['booking_id']);

            // Customer hanya boleh melihat booking miliknya sendiri
            if ($booking && $user->role !== 'admin' && (int) $booking->user_id !== (int) $user->id) {
                $booking = null;
            }
        }

        $payment = null;

        if ($booking && class_exists(\App\Models\Payment::class)) {
            $payment = \App\Models\Payment::where('booking_id', $booking->id)->first();
        }

        return view('notifications.show', [
            'item'    => $item,
            'booking' => $booking,
            'payment' => $payment,
        ]);
    }

    /**
     * Buka notifikasi lalu langsung ke halaman terkait (dipakai link lama).
     */
    public function open(Request $request, string $notification): RedirectResponse
    {
        $item = $request->user()->notifications()->findOrFail($notification);
        $item->markAsRead();

        $url = $item->data['url'] ?? null;

        if (is_string($url) && str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return redirect($url);
        }

        return redirect()->route('notifications.index');
    }

    /**
     * Tandai semua notifikasi sebagai sudah dibaca.
     */
    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }

    /**
     * Hapus satu notifikasi.
     */
    public function destroy(Request $request, string $notification): RedirectResponse
    {
        $request->user()->notifications()->findOrFail($notification)->delete();

        return redirect()
            ->route('notifications.index')
            ->with('success', 'Notifikasi dihapus.');
    }
}
