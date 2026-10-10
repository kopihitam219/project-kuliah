<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Judul bagian About Coach di Home bisa diganti admin (coach). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coaches', function (Blueprint $table) {
            $table->string('section_label', 60)->nullable();
            $table->string('section_title', 120)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('coaches', function (Blueprint $table) {
            $table->dropColumn(['section_label', 'section_title']);
        });
    }
};
