<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminPaymentController extends Controller
{
    /**
     * Bukti pembayaran (halaman siap cetak).
     */
    public function receipt(Payment $payment): View
    {
        abort_unless($payment->status === 'paid', 404);

        $payment->load('booking.user');

        return view('admin.payments.receipt', compact('payment'));
    }

    /**
     * Bukti pembayaran yang di-upload customer (file privat, hanya admin).
     */
    public function proof(Payment $payment)
    {
        abort_unless($payment->hasProof(), 404);

        return response()->file(\Illuminate\Support\Facades\Storage::disk(Payment::PROOF_DISK)->path($payment->proof_path));
    }

    /**
     * Admin mengonfirmasi pembayaran QRIS / transfer setelah mengecek mutasi.
     */
    public function verify(Payment $payment): RedirectResponse
    {
        if ($payment->status !== 'verifying') {
            return redirect()->route('admin.dashboard')->with('error', 'Pembayaran ini tidak sedang menunggu verifikasi.');
        }

        $payment->update(['status' => 'paid', 'paid_at' => now()]);

        $booking = $payment->booking;

        if ($booking) {
            PaymentController::autoApprove($booking);
        }

        $this->notifyCustomer(
            $payment,
            'Pembayaran dikonfirmasi',
            "Pembayaran {$payment->amount_label} untuk booking Anda sudah kami terima."
                . ($booking && $booking->fresh()->status === 'booked' ? ' Booking Anda sudah dikonfirmasi.' : ' Booking menunggu persetujuan admin.')
        );

        return redirect()->route('admin.dashboard')->with('success', 'Pembayaran dikonfirmasi sebagai LUNAS.');
    }

    /**
     * Tandai lunas karena customer membayar cash ke admin.
     * Penerima otomatis = admin yang sedang login.
     */
    public function markCash(Request $request, Payment $payment): RedirectResponse
    {
        if ($payment->status === 'paid') {
            return redirect()->route('admin.dashboard')->with('error', 'Pembayaran ini sudah lunas.');
        }

        $validated = $request->validate([
            'payment_note' => ['nullable', 'string', 'max:255'],
        ]);

        $receivedBy = 'Admin · ' . $request->user()->name;

        $payment->update([
            'method'       => 'cash',
            'status'       => 'paid',
            'va_number'    => null,
            'expires_at'   => null,
            'paid_at'      => now(),
            'received_by'  => $receivedBy,
            'payment_note' => trim((string) ($validated['payment_note'] ?? '')) ?: null,
        ]);

        $booking = $payment->booking;

        if ($booking) {
            PaymentController::autoApprove($booking);
        }

        $this->notifyCustomer(
            $payment,
            'Pembayaran cash diterima',
            "Pembayaran cash {$payment->amount_label} untuk booking Anda sudah diterima admin. Terima kasih!"
        );

        return redirect()->route('admin.dashboard')->with('success', "Pembayaran cash dicatat LUNAS (diterima oleh {$receivedBy}).");
    }

    /**
     * Admin menolak: dana belum masuk. Customer bisa membayar ulang.
     */
    public function rejectVerification(Payment $payment): RedirectResponse
    {
        if ($payment->status !== 'verifying') {
            return redirect()->route('admin.dashboard')->with('error', 'Pembayaran ini tidak sedang menunggu verifikasi.');
        }

        // Bukti lama dihapus, customer upload ulang setelah membayar
        $payment->deleteProof();

        $payment->update([
            'status'            => 'unpaid',
            'method'            => null,
            'va_number'         => null,
            'expires_at'        => null,
            'proof_path'        => null,
            'proof_uploaded_at' => null,
        ]);

        $this->notifyCustomer(
            $payment,
            'Pembayaran belum diterima',
            "Pembayaran {$payment->amount_label} untuk booking Anda belum kami temukan. Silakan cek kembali atau lakukan pembayaran ulang."
        );

        return redirect()->route('admin.dashboard')->with('success', 'Verifikasi ditolak. Customer diminta membayar ulang.');
    }

    private function notifyCustomer(Payment $payment, string $title, string $message): void
    {
        $booking  = $payment->booking;
        $customer = $booking?->user;

        if (! $customer || ! class_exists(\App\Notifications\BookingActivity::class)) {
            return;
        }

        try {
            $customer->notify(new \App\Notifications\BookingActivity(
                $payment->status === 'paid' ? 'paid' : 'rejected',
                $title,
                $message,
                route('payment.booking', $booking, false),
                $booking->id,
            ));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
