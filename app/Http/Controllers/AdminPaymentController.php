<?php

namespace App\Http\Controllers;

use App\Models\Payment;
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
}
