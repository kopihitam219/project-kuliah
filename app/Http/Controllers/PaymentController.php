<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Pembayaran booking lesson (MODE DUMMY / SIMULASI).
 * Tidak terhubung ke payment gateway sungguhan.
 */
class PaymentController extends Controller
{
    /**
     * Halaman pembayaran sebuah booking.
     */
    public function show(Request $request, Booking $booking): View|RedirectResponse
    {
        $this->authorizeOwner($request, $booking);

        if ($redirect = $this->redirectIfOffline($booking)) {
            return $redirect;
        }

        $payment = Payment::forBooking($booking);
        $payment->releaseIfExpired();

        return view('payments.booking', [
            'booking' => $booking,
            'payment' => $payment->fresh(),
            'methods' => Payment::METHODS,
        ]);
    }

    /**
     * Customer memilih metode pembayaran.
     */
    public function chooseMethod(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorizeOwner($request, $booking);

        if ($redirect = $this->redirectIfOffline($booking)) {
            return $redirect;
        }

        $validated = $request->validate([
            'method' => ['required', Rule::in(array_keys(Payment::METHODS))],
        ], [
            'method.required' => 'Pilih metode pembayaran terlebih dahulu.',
        ]);

        $payment = Payment::forBooking($booking);

        if ($error = $this->blockedReason($booking, $payment)) {
            return back()->withErrors(['payment' => $error]);
        }

        $method = $validated['method'];
        $prefix = Payment::METHODS[$method]['prefix'];

        $payment->update([
            'method'     => $method,
            'status'     => 'pending',
            'va_number'  => $prefix
                ? $prefix . str_pad((string) $booking->id, 6, '0', STR_PAD_LEFT) . random_int(1000, 9999)
                : null,
            'expires_at' => now()->addHours(Payment::EXPIRES_IN_HOURS),
        ]);

        return redirect()->route('payment.booking', $booking);
    }

    /**
     * Ganti metode pembayaran (kembali ke pilihan metode).
     */
    public function resetMethod(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorizeOwner($request, $booking);

        if ($redirect = $this->redirectIfOffline($booking)) {
            return $redirect;
        }

        $payment = Payment::forBooking($booking);

        if ($payment->status === 'pending') {
            $payment->update([
                'status'     => 'unpaid',
                'method'     => null,
                'va_number'  => null,
                'expires_at' => null,
            ]);
        }

        return redirect()->route('payment.booking', $booking);
    }

    /**
     * SIMULASI: customer menekan "Saya sudah bayar" -> pembayaran langsung lunas.
     */
    public function confirm(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorizeOwner($request, $booking);

        if ($redirect = $this->redirectIfOffline($booking)) {
            return $redirect;
        }

        $payment = Payment::forBooking($booking);
        $payment->releaseIfExpired();

        if ($payment->status !== 'pending') {
            return redirect()
                ->route('payment.booking', $booking)
                ->withErrors(['payment' => 'Pilih metode pembayaran terlebih dahulu.']);
        }

        if ($error = $this->blockedReason($booking, $payment)) {
            return back()->withErrors(['payment' => $error]);
        }

        $payment->update([
            'status'  => 'paid',
            'paid_at' => now(),
        ]);

        $this->notifyAdmins($request->user(), $booking, $payment);

        return redirect()
            ->route('payment.booking', $booking)
            ->with('payment_success', 'Pembayaran berhasil! Booking Anda sekarang menunggu konfirmasi admin.');
    }

    /* ---------------------------------------------------------------
     | HELPER
     * --------------------------------------------------------------- */
    private function authorizeOwner(Request $request, Booking $booking): void
    {
        abort_unless((int) $booking->user_id === (int) $request->user()->id, 403);
    }

    /**
     * Booking offline dibuat admin dan sudah dibayar langsung di tempat,
     * jadi tidak perlu (dan tidak boleh) dibayar online lagi.
     */
    private function redirectIfOffline(Booking $booking): ?RedirectResponse
    {
        if ($booking->source !== 'offline') {
            return null;
        }

        return redirect()
            ->route('booking', ['date' => $booking->booking_date->format('Y-m-d')])
            ->with('booking_success', 'Booking ini dibuat oleh admin dan sudah dibayar langsung di tempat. Tidak perlu pembayaran online.');
    }

    private function blockedReason(Booking $booking, Payment $payment): ?string
    {
        if ($payment->status === 'paid') {
            return 'Booking ini sudah lunas.';
        }

        if (! in_array($booking->status, ['pending', 'booked'], true)) {
            return 'Booking ini sudah tidak aktif sehingga tidak dapat dibayar.';
        }

        return null;
    }

    private function notifyAdmins(User $customer, Booking $booking, Payment $payment): void
    {
        if (! class_exists(\App\Notifications\BookingActivity::class)) {
            return; // fitur notifikasi belum terpasang
        }

        try {
            $admins = User::where('role', 'admin')->get();

            if ($admins->isEmpty()) {
                return;
            }

            $schedule = $booking->booking_date->locale('id')->translatedFormat('D, d M Y')
                . ' ' . substr($booking->start_time, 0, 5) . '–' . substr($booking->end_time, 0, 5);

            Notification::send($admins, new \App\Notifications\BookingActivity(
                'paid',
                'Pembayaran diterima',
                "{$customer->name} membayar booking {$schedule} via {$payment->method_label} ({$payment->amount_label}).",
                route('admin.dashboard', [], false) . '#booking-list',
                $booking->id,
            ));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
