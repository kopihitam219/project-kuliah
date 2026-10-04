<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Program extends Model
{
    /** Gambar bawaan jika program belum punya foto */
    public const DEFAULT_IMAGE = 'images/background.golf.jpeg';

    /** Pilihan fokus foto => [label, nilai CSS background-position] */
    public const POSITIONS = [
        'left'         => ['Kiri',         'left center'],
        'center-left'  => ['Kiri tengah',  '35% center'],
        'center'       => ['Tengah',       'center'],
        'center-right' => ['Kanan tengah', '65% center'],
        'right'        => ['Kanan',        'right center'],
    ];

    protected $fillable = [
        'level',
        'name',
        'description',
        'features',
        'image',
        'image_position',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'features'   => 'array',
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    /* ---------------------------------------------------------------
     | SCOPES
     * --------------------------------------------------------------- */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /* ---------------------------------------------------------------
     | ACCESSORS
     * --------------------------------------------------------------- */
    public function getImageUrlAttribute(): string
    {
        $path = $this->image ?: self::DEFAULT_IMAGE;

        if (Str::startsWith($path, ['http://', 'https://', '//'])) {
            return $path;
        }

        if (file_exists(public_path($path))) {
            return asset($path);
        }

        return Storage::disk('public')->url($path);
    }

    public function getImageCssPositionAttribute(): string
    {
        return self::POSITIONS[$this->image_position][1] ?? 'center';
    }

    /** Fitur dalam bentuk teks, satu per baris (untuk textarea form) */
    public function getFeaturesTextAttribute(): string
    {
        return implode("\n", $this->features ?? []);
    }
}
