<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Event extends Model
{
    public const STATUSES = [
        'open'   => 'Registrasi Dibuka',
        'closed' => 'Registrasi Ditutup',
        'full'   => 'Kuota Penuh',
    ];

    protected $fillable = [
        'title',
        'description',
        'poster',
        'event_date',
        'start_time',
        'end_time',
        'location',
        'registration_status',
        'price',
        'price_unit',
        'note',
        'is_active',
    ];

    protected $casts = [
        'event_date' => 'date',
        'is_active'  => 'boolean',
        'price'      => 'integer',
    ];

    /* ---------------------------------------------------------------
     | SCOPES
     * --------------------------------------------------------------- */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->whereDate('event_date', '>=', today());
    }

    public function scopePast(Builder $query): Builder
    {
        return $query->whereDate('event_date', '<', today());
    }

    public function scopeChronological(Builder $query): Builder
    {
        return $query->orderBy('event_date')->orderBy('start_time');
    }

    /* ---------------------------------------------------------------
     | HELPER
     * --------------------------------------------------------------- */
    public function isPast(): bool
    {
        return $this->event_date && $this->event_date->lt(today());
    }

    public function canRegister(): bool
    {
        return ! $this->isPast() && $this->registration_status === 'open';
    }

    /* ---------------------------------------------------------------
     | ACCESSORS
     * --------------------------------------------------------------- */
    public function getPosterUrlAttribute(): ?string
    {
        $path = $this->poster;

        if (! $path) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', '//'])) {
            return $path;
        }

        if (file_exists(public_path($path))) {
            return asset($path);
        }

        return Storage::disk('public')->url($path);
    }

    /** Kata pertama judul (warna putih) */
    public function getTitleFirstAttribute(): string
    {
        return Str::before(trim($this->title), ' ');
    }

    /** Sisa judul setelah kata pertama (warna hijau) */
    public function getTitleRestAttribute(): string
    {
        $title = trim($this->title);

        return Str::contains($title, ' ') ? Str::after($title, ' ') : '';
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->isPast()) {
            return 'Event Selesai';
        }

        return self::STATUSES[$this->registration_status] ?? 'Registrasi Dibuka';
    }

    public function getDateLabelAttribute(): string
    {
        return $this->event_date
            ? $this->event_date->locale('id')->translatedFormat('d F Y')
            : '-';
    }

    public function getTimeRangeAttribute(): string
    {
        $start = $this->start_time ? substr($this->start_time, 0, 5) : null;
        $end   = $this->end_time ? substr($this->end_time, 0, 5) : null;

        if ($start && $end) {
            return "{$start} – {$end}";
        }

        return $start ?? '-';
    }

    public function getPriceLabelAttribute(): string
    {
        return $this->price > 0
            ? 'Rp' . number_format($this->price, 0, ',', '.')
            : 'Gratis';
    }
}
