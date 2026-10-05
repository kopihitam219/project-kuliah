<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lapangan (driving range) dan jenis lesson pada booking.
     * Booking lama otomatis dianggap "Lesson Driving Range" tanpa lapangan.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'lesson_type')) {
                $table->string('lesson_type', 20)->default('driving')->after('end_time');
            }

            if (! Schema::hasColumn('bookings', 'location_id')) {
                $table->unsignedBigInteger('location_id')->nullable()->after('lesson_type')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'location_id')) {
                $table->dropIndex(['location_id']);
                $table->dropColumn('location_id');
            }

            if (Schema::hasColumn('bookings', 'lesson_type')) {
                $table->dropColumn('lesson_type');
            }
        });
    }
};
