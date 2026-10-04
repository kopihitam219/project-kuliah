<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ContactLocation extends Model
{
    protected $fillable = [
        'name',
        'area',
        'note',
        'maps_query',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /** Kata kunci pencarian peta (pakai nama + area jika maps_query kosong) */
    public function getMapsSearchAttribute(): string
    {
        return $this->maps_query ?: trim($this->name . ' ' . $this->area);
    }

    public function getMapEmbedUrlAttribute(): string
    {
        return 'https://maps.google.com/maps?q=' . urlencode($this->maps_search) . '&z=15&output=embed';
    }

    public function getMapLinkAttribute(): string
    {
        return 'https://www.google.com/maps/search/?api=1&query=' . urlencode($this->maps_search);
    }
}
