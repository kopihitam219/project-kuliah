<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambah kolom video_file untuk video yang diunggah langsung
     * (selain link YouTube di kolom video_url).
     */
    public function up(): void
    {
        if (Schema::hasColumn('galleries', 'video_file')) {
            return;
        }

        Schema::table('galleries', function (Blueprint $table) {
            $table->string('video_file')->nullable()->after('video_url');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('galleries', 'video_file')) {
            Schema::table('galleries', function (Blueprint $table) {
                $table->dropColumn('video_file');
            });
        }
    }
};
