<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Support\BookingRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Pembayaran booking lesson oleh customer (QRIS / transfer rekening).
 * - Mode demo : simulasi, langsung lunas.
 * - Mode live : menunggu verifikasi admin setelah customer transfer.
 * Mode diatur di Settings > Pembayaran.
 */
class PaymentController extends Controller
{
    public function show(Request $request, Booking $booking): View|RedirectResponse
    {
        $this->authorizeOwner($request, $booking);

        if ($redirect = $this->redirectIfOffline($booking)) {
            return $redirect;
        }

        $payment = Payment::forBooking($booking);
        $payment->releaseIfExpired();

        // Metode yang dipilih sudah tidak tersedia (dinonaktifkan / rekening dihapus): pilih ulang
        if ($payment->status === 'pending' && ! array_key_exists((string) $payment->method, Payment::methods())) {
            $payment->update(['status' => 'unpaid', 'method' => null, 'va_number' => null, 'expires_at' => null]);
        }

        $payment = $payment->fresh();

        return view('payments.booking', [
            'booking'      => $booking,
            'payment'      => $payment,
            'methods'      => Payment::methods(),
            'activeMethod' => $payment->method ? Payment::methodConfig($payment->method) : null,
        ]);
    }

    public function chooseMethod(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorizeOwner($request, $booking);

        if ($redirect = $this->redirectIfOffline($booking)) {
            return $redirect;
        }

        $validated = $request->validate([
            'method' => ['required', Rule::in(array_keys(Payment::methods()))],
        ], [
            'method.required' => 'Pilih metode pembayaran terlebih dahulu.',
            'method.in'       => 'Metode pembayaran tidak tersedia.',
        ]);

        $payment = Payment::forBooking($booking);

        if ($error = $this->blockedReason($booking, $payment)) {
            return back()->withErrors(['payment' => $error]);
        }

        $payment->update([
            'method'     => $validated['method'],
            'status'     => 'pending',
            'va_number'  => null,
            'expires_at' => now()->addHours(BookingRules::paymentExpiryHours()),
        ]);

        return redirect()->route('payment.booking', $booking);
    }

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
     * Customer menekan "Saya sudah bayar".
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

        // Mode live: tunggu admin mengecek mutasi
        if ($payment->needsVerification()) {
            $payment->update(['status' => 'verifying']);

            $this->notifyAdmins(
                $request->user(),
                $booking,
                $payment,
                'Pembayaran perlu dicek',
                'mengonfirmasi sudah membayar'
            );

            return redirect()
                ->route('payment.booking', $booking)
                ->with('payment_success', 'Terima kasih! Admin akan mengecek pembayaran Anda dan mengonfirmasi secepatnya.');
        }

        // Mode demo: langsung lunas
        $payment->update(['status' => 'paid', 'paid_at' => now()]);
        self::autoApprove($booking);

        $this->notifyAdmins($request->user(), $booking, $payment, 'Pembayaran diterima', 'membayar');

        return redirect()
            ->route('payment.booking', $booking)
            ->with('payment_success', $booking->fresh()->status === 'booked'
                ? 'Pembayaran berhasil dan booking Anda sudah dikonfirmasi!'
                : 'Pembayaran berhasil! Booking Anda sekarang menunggu konfirmasi admin.');
    }

    /* ---------------------------------------------------------------
     | HELPER
     * --------------------------------------------------------------- */

    /**
     * Setujui otomatis setelah lunas (jika diaktifkan di Settings).
     * Disimpan tanpa event agar tidak memicu notifikasi ganda.
     */
    public static function autoApprove(Booking $booking): void
    {
        if (BookingRules::autoApprovePaid() && $booking->status === 'pending') {
            $booking->forceFill(['status' => 'booked'])->saveQuietly();
        }
    }

    private function authorizeOwner(Request $request, Booking $booking): void
    {
        abort_unless((int) $booking->user_id === (int) $request->user()->id, 403);
    }

    /**
     * Booking offline dibuat admin dan sudah dibayar langsung di tempat.
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

        if ($payment->status === 'verifying') {
            return 'Pembayaran sedang dicek admin.';
        }

        if (! in_array($booking->status, ['pending', 'booked'], true)) {
            return 'Booking ini sudah tidak aktif sehingga tidak dapat dibayar.';
        }

        return null;
    }

    private function notifyAdmins(User $customer, Booking $booking, Payment $payment, string $title, string $verb): void
    {
        if (! class_exists(\App\Notifications\BookingActivity::class)) {
            return;
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
                $title,
                "{$customer->name} {$verb} booking {$schedule} via {$payment->method_label} ({$payment->amount_label}).",
                route('admin.dashboard', [], false) . '#booking-list',
                $booking->id,
            ));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
