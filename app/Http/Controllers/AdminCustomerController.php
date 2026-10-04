<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AdminCustomerController extends Controller
{
    public const SORTS = [
        'newest' => 'Terbaru',
        'name'   => 'Nama A–Z',
        'most'   => 'Booking terbanyak',
        'recent' => 'Booking terakhir',
    ];

    public const STATUSES = [
        'pending'   => 'Pending',
        'booked'    => 'Booked',
        'cancelled' => 'Dibatalkan',
        'rejected'  => 'Ditolak',
    ];

    /**
     * Daftar customer: tab Member (punya akun) & Offline (dari booking admin).
     */
    public function index(Request $request): View
    {
        $tab    = $request->query('tab') === 'offline' ? 'offline' : 'member';
        $search = trim((string) $request->query('q', ''));
        $sort   = array_key_exists((string) $request->query('sort'), self::SORTS)
            ? $request->query('sort')
            : 'newest';

        $stats = [
            'members'   => User::where('role', 'customer')->count(),
            'new_month' => User::where('role', 'customer')
                ->where('created_at', '>=', now()->startOfMonth())
                ->count(),
            'active_30' => Booking::whereNotNull('user_id')
                ->where('created_at', '>=', now()->subDays(30))
                ->distinct()
                ->count('user_id'),
            'offline'   => Booking::whereNull('user_id')
                ->whereNotNull('offline_customer_phone')
                ->distinct()
                ->count('offline_customer_phone'),
        ];

        $customers = $tab === 'member'
            ? $this->memberQuery($search, $sort)->paginate(15)->withQueryString()
            : $this->offlineQuery($search, $sort)->paginate(15)->withQueryString();

        return view('admin.customers.index', [
            'customers' => $customers,
            'tab'       => $tab,
            'search'    => $search,
            'sort'      => $sort,
            'sorts'     => self::SORTS,
            'stats'     => $stats,
        ]);
    }

    /**
     * Detail member (customer yang punya akun).
     */
    public function show(User $user): View
    {
        abort_unless($user->role === 'customer', 404);

        $bookings = Booking::where('user_id', $user->id)
            ->orderByDesc('booking_date')
            ->orderByDesc('start_time')
            ->get();

        return view('admin.customers.show', [
            'user'     => $user,
            'phone'    => $user->getAttributes()['phone'] ?? null,
            'waUrl'    => self::whatsappUrl($user->getAttributes()['phone'] ?? null),
            'bookings' => $bookings,
            'summary'  => $this->summary($bookings),
            'upcoming' => $this->upcoming($bookings),
            'statuses' => self::STATUSES,
        ]);
    }

    /**
     * Detail customer offline (dikelompokkan berdasarkan nomor HP).
     */
    public function offline(Request $request): View|RedirectResponse
    {
        $phone = trim((string) $request->query('phone', ''));

        if ($phone === '') {
            return redirect()->route('admin.customers.index', ['tab' => 'offline']);
        }

        $bookings = Booking::whereNull('user_id')
            ->where('offline_customer_phone', $phone)
            ->orderByDesc('booking_date')
            ->orderByDesc('start_time')
            ->get();

        abort_if($bookings->isEmpty(), 404);

        $latest = $bookings->first();

        return view('admin.customers.offline', [
            'name'     => $latest->offline_customer_name ?: 'Customer offline',
            'phone'    => $phone,
            'email'    => $bookings->pluck('offline_customer_email')->filter()->first(),
            'waUrl'    => self::whatsappUrl($phone),
            'bookings' => $bookings,
            'summary'  => $this->summary($bookings),
            'upcoming' => $this->upcoming($bookings),
            'statuses' => self::STATUSES,
        ]);
    }

    /* ---------------------------------------------------------------
     | QUERY
     * --------------------------------------------------------------- */
    private function memberQuery(string $search, string $sort)
    {
        $hasPhone = Schema::hasColumn('users', 'phone');

        $query = User::query()
            ->where('role', 'customer')
            ->select('users.*')
            ->addSelect([
                'bookings_count' => Booking::selectRaw('COUNT(*)')
                    ->whereColumn('bookings.user_id', 'users.id'),
                'last_booking_date' => Booking::select('booking_date')
                    ->whereColumn('bookings.user_id', 'users.id')
                    ->orderByDesc('booking_date')
                    ->limit(1),
            ]);

        if ($search !== '') {
            $query->where(function ($q) use ($search, $hasPhone) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");

                if ($hasPhone) {
                    $q->orWhere('phone', 'like', "%{$search}%");
                }
            });
        }

        return match ($sort) {
            'name'   => $query->orderBy('name'),
            'most'   => $query->orderByDesc('bookings_count')->orderBy('name'),
            'recent' => $query->orderByDesc('last_booking_date')->orderBy('name'),
            default  => $query->orderByDesc('users.created_at'),
        };
    }

    private function offlineQuery(string $search, string $sort)
    {
        $query = DB::table('bookings')
            ->whereNull('user_id')
            ->whereNotNull('offline_customer_phone')
            ->selectRaw('
                offline_customer_phone AS phone,
                MAX(offline_customer_name) AS name,
                MAX(offline_customer_email) AS email,
                COUNT(*) AS bookings_count,
                MAX(booking_date) AS last_booking_date,
                MIN(created_at) AS first_seen
            ')
            ->groupBy('offline_customer_phone');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('offline_customer_name', 'like', "%{$search}%")
                    ->orWhere('offline_customer_phone', 'like', "%{$search}%")
                    ->orWhere('offline_customer_email', 'like', "%{$search}%");
            });
        }

        return match ($sort) {
            'name'   => $query->orderBy('name'),
            'most'   => $query->orderByDesc('bookings_count')->orderBy('name'),
            'recent' => $query->orderByDesc('last_booking_date')->orderBy('name'),
            default  => $query->orderByDesc('first_seen'),
        };
    }

    /* ---------------------------------------------------------------
     | HELPER
     * --------------------------------------------------------------- */
    private function summary(Collection $bookings): array
    {
        return [
            'total'     => $bookings->count(),
            'booked'    => $bookings->where('status', 'booked')->count(),
            'pending'   => $bookings->where('status', 'pending')->count(),
            'cancelled' => $bookings->whereIn('status', ['cancelled', 'rejected'])->count(),
        ];
    }

    private function upcoming(Collection $bookings): ?Booking
    {
        return $bookings
            ->filter(fn ($booking) => in_array($booking->status, ['pending', 'booked'], true)
                && $booking->booking_date
                && $booking->booking_date->gte(today()))
            ->sortBy(fn ($booking) => $booking->booking_date->format('Y-m-d') . ' ' . $booking->start_time)
            ->first();
    }

    /**
     * "0858 8680 3126" -> "https://wa.me/6285886803126"
     */
    public static function whatsappUrl(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        }

        return 'https://wa.me/' . $digits;
    }
}
