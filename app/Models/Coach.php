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
        'bio', 'experiences', 'certifications', 'achievements', 'section_label', 'section_title',
    ];

    protected $casts = [
        'skills'           => 'array',
        'experiences'      => 'array',
        'certifications'   => 'array',
        'achievements'     => 'array',
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

    /** Coach utama yang tampil di About Coach (Home). */
    public static function main(): ?self
    {
        return static::ordered()->first();
    }

    /** Nama yang tampil ke member di chat & notifikasi (admin = coach). */
    public static function chatName(): string
    {
        try {
            $name = static::main()?->name;
        } catch (\Throwable $e) {
            $name = null;
        }

        return $name ?: 'Admin ' . \App\Support\Brand::name();
    }

    public static function chatPhoto(): ?string
    {
        try {
            return static::main()?->photo_url;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Label kecil di atas judul bagian, contoh: "About coach". */
    public function getSectionLabelTextAttribute(): string
    {
        return $this->section_label ?: 'About coach';
    }

    /** Judul bagian; kata di dalam [kurung siku] tampil hijau. */
    public function getSectionTitleTextAttribute(): string
    {
        return $this->section_title ?: 'Kenali [coach] Anda';
    }

    public function getSectionTitleHtmlAttribute(): string
    {
        return preg_replace('/\[(.+?)\]/u', '<span>$1</span>', e($this->section_title_text));
    }

    public function getSkillsTextAttribute(): string
    {
        return implode("\n", $this->skills ?? []);
    }

    public function getCertificationsTextAttribute(): string
    {
        return implode("\n", $this->certifications ?? []);
    }

    public function getAchievementsTextAttribute(): string
    {
        return implode("\n", $this->achievements ?? []);
    }

    /** Pengalaman sebagai teks: satu baris "periode | keterangan". */
    public function getExperiencesTextAttribute(): string
    {
        return collect($this->experiences ?? [])
            ->map(fn ($e) => trim(($e['period'] ?? '') . ' | ' . ($e['text'] ?? ''), ' |'))
            ->implode("\n");
    }
}
