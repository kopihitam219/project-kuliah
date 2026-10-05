<?php

namespace App\Models;

use App\Observers\BookingObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([BookingObserver::class])]
class Booking extends Model
{
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | Mass Assignment
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'user_id',
        'offline_customer_name',
        'offline_customer_phone',
        'offline_customer_email',
        'booking_date',
        'start_time',
        'end_time',
        'lesson_type',
        'location_id',
        'course_venue',
        'status',
        'source',
        'admin_notes',
    ];

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected function casts(): array
    {
        return [
            'booking_date' => 'date',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationship
    |--------------------------------------------------------------------------
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isBooked(): bool
    {
        return $this->status === 'booked';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /*
    |--------------------------------------------------------------------------
    | Jenis lesson & lapangan
    |--------------------------------------------------------------------------
    | Dibaca lewat getAttributes() supaya aman walau kolom tidak ikut di-select.
    */

    public function lessonType(): string
    {
        return $this->getAttributes()['lesson_type'] ?? 'driving';
    }

    public function isCourse(): bool
    {
        return $this->lessonType() === 'course';
    }

    public function getLessonLabelAttribute(): string
    {
        return \App\Support\BookingRules::lessonLabel($this->lessonType());
    }

    public function getPlaceLabelAttribute(): ?string
    {
        $attributes = $this->getAttributes();

        return \App\Support\BookingRules::placeFor(
            $this->lessonType(),
            $attributes['location_id'] ?? null,
            $attributes['course_venue'] ?? null
        );
    }
}
