<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Coach extends Model
{
    protected $fillable = [
        'name', 'role', 'badge', 'years_experience', 'students', 'skills', 'quote', 'photo', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'skills'           => 'array',
        'is_active'        => 'boolean',
        'sort_order'       => 'integer',
        'years_experience' => 'integer',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /** URL foto, atau null bila belum ada (tampil ilustrasi siluet). */
    public function getPhotoUrlAttribute(): ?string
    {
        if (! $this->photo) {
            return null;
        }

        if (Str::startsWith($this->photo, ['http://', 'https://', '//'])) {
            return $this->photo;
        }

        return Storage::disk('public')->url($this->photo);
    }

    public function getSkillsTextAttribute(): string
    {
        return implode("\n", $this->skills ?? []);
    }
}
