<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Customer
            |--------------------------------------------------------------------------
            */

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Booking Date & Time
            |--------------------------------------------------------------------------
            */

            $table->date('booking_date');

            $table->time('start_time');

            $table->time('end_time');

            /*
            |--------------------------------------------------------------------------
            | Booking Status
            |--------------------------------------------------------------------------
            |
            | pending  = Customer membuat booking, menunggu persetujuan Admin
            | booked   = Sudah disetujui / booking offline dari Admin
            | rejected = Ditolak Admin
            |
            */

            $table->enum('status', [
                'pending',
                'booked',
                'rejected',
            ])->default('pending');

            /*
            |--------------------------------------------------------------------------
            | Booking Source
            |--------------------------------------------------------------------------
            |
            | online  = dibuat oleh Customer
            | offline = dibuat langsung oleh Admin
            |
            */

            $table->enum('source', [
                'online',
                'offline',
            ])->default('online');

            /*
            |--------------------------------------------------------------------------
            | Admin Notes
            |--------------------------------------------------------------------------
            */

            $table->text('admin_notes')->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Index
            |--------------------------------------------------------------------------
            */

            $table->index([
                'booking_date',
                'start_time',
                'end_time',
            ]);

            $table->index([
                'booking_date',
                'status',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};