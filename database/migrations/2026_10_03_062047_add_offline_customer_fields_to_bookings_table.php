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
        Schema::table('bookings', function (Blueprint $table) {
            /*
            |--------------------------------------------------------------------------
            | Customer Account
            |--------------------------------------------------------------------------
            |
            | Booking member menggunakan user_id.
            | Booking offline boleh tidak memiliki user_id.
            |
            */

            $table->foreignId('user_id')
                ->nullable()
                ->change();

            /*
            |--------------------------------------------------------------------------
            | Offline Customer
            |--------------------------------------------------------------------------
            |
            | Dipakai jika customer belum memiliki akun/member.
            |
            */

            $table->string('offline_customer_name')
                ->nullable()
                ->after('user_id');

            $table->string('offline_customer_phone')
                ->nullable()
                ->after('offline_customer_name');

            $table->string('offline_customer_email')
                ->nullable()
                ->after('offline_customer_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'offline_customer_name',
                'offline_customer_phone',
                'offline_customer_email',
            ]);

            $table->foreignId('user_id')
                ->nullable(false)
                ->change();
        });
    }
};