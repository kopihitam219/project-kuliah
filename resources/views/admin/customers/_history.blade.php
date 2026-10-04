{{-- Riwayat booking. Variabel: $bookings, $statuses --}}
<div class="table-card">
    <div class="table-title">Riwayat booking</div>

    @if ($bookings->isEmpty())
        <div class="empty-state" style="border: 0;">Belum ada booking.</div>
    @else
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Tanggal</th>
                        <th>Jam</th>
                        <th>Tipe</th>
                        <th>Status</th>
                        <th>Catatan admin</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($bookings as $booking)
                        @php
                            $status = strtolower($booking->status ?? 'pending');
                        @endphp
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $booking->booking_date?->locale('id')->translatedFormat('d M Y') ?? '-' }}</td>
                            <td>
                                {{ $booking->start_time ? substr($booking->start_time, 0, 5) : '-' }}
                                –
                                {{ $booking->end_time ? substr($booking->end_time, 0, 5) : '-' }}
                            </td>
                            <td>{{ strtoupper($booking->source ?: 'online') }}</td>
                            <td>
                                <span class="status-pill {{ array_key_exists($status, $statuses) ? $status : 'neutral' }}">
                                    {{ $statuses[$status] ?? ucfirst($status) }}
                                </span>
                            </td>
                            <td class="muted">{{ $booking->admin_notes ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
