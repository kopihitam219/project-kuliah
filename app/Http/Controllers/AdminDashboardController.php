<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\User;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(): View
    {
        // Jumlah booking yang menunggu persetujuan
        $pendingBooking = Booking::where('status', 'pending')->count();

        // Jumlah booking yang sudah disetujui
        $booked = Booking::where('status', 'booked')->count();

        // Jumlah customer/member
        $totalCustomer = User::where('role', 'customer')->count();

        // Daftar booking
        $bookings = Booking::with('user')
            ->latest('booking_date')
            ->latest('start_time')
            ->get();

        return view('admin.dashboard', [
            'pendingBooking' => $pendingBooking,
            'booked' => $booked,
            'totalCustomer' => $totalCustomer,
            'bookings' => $bookings,
        ]);
    }

    public function approve(Booking $booking)
    {
        $booking->update(['status' => 'booked']);

        return redirect()
            ->route('admin.dashboard')
            ->with('success', 'Booking berhasil disetujui.');
    }

    public function reject(Booking $booking)
    {
        $booking->update(['status' => 'cancelled']);

        return redirect()
            ->route('admin.dashboard')
            ->with('success', 'Booking berhasil ditolak.');
    }}
