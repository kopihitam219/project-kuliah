<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ScheduleBlock extends Model
{
    protected $fillable = [
        'date',
        'start_time',
        'end_time',
        'reason',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Blok yang bertabrakan dengan tanggal & jam tertentu.
     */
    public function scopeOverlapping(Builder $query, $date, $start, $end): Builder
    {
        $start = self::normalizeTime($start);
        $end   = self::normalizeTime($end);

        return $query
            ->whereDate('date', Carbon::parse($date)->toDateString())
            ->where(function ($q) use ($start, $end) {
                $q->whereNull('start_time')
                    ->orWhere(function ($q2) use ($start, $end) {
                        $q2->where('start_time', '<', $end)
                            ->where('end_time', '>', $start);
                    });
            });
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->whereDate('date', '>=', today());
    }

    public function isAllDay(): bool
    {
        return ! $this->start_time || ! $this->end_time;
    }

    public function getTimeLabelAttribute(): string
    {
        return $this->isAllDay()
            ? 'Seharian'
            : substr($this->start_time, 0, 5) . ' – ' . substr($this->end_time, 0, 5);
    }

    public function getDateLabelAttribute(): string
    {
        return $this->date->locale('id')->translatedFormat('l, d F Y');
    }

    /**
     * Booking aktif (pending/booked) yang bentrok dengan blok ini.
     */
    public function conflictingBookings()
    {
        $query = Booking::with('user')
            ->whereDate('booking_date', $this->date->toDateString())
            ->whereIn('status', ['pending', 'booked'])
            ->orderBy('start_time');

        if (! $this->isAllDay()) {
            $query->where('start_time', '<', $this->end_time)
                ->where('end_time', '>', $this->start_time);
        }

        return $query->get();
    }

    /** "09:00" -> "09:00:00" agar perbandingan jam konsisten */
    public static function normalizeTime($time): string
    {
        return Carbon::parse((string) $time)->format('H:i:s');
    }
}
