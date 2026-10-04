<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('programs')) {
            return;
        }

        Schema::create('programs', function (Blueprint $table) {
            $table->id();

            // Isi kartu program
            $table->string('level', 50);                 // contoh: BEGINNER
            $table->string('name', 100);                 // contoh: Beginner Program
            $table->string('description')->nullable();
            $table->json('features')->nullable();        // daftar poin fitur

            // Foto kartu
            $table->string('image')->nullable();
            $table->string('image_position', 20)->default('center'); // left | center-left | center | center-right | right

            $table->unsignedInteger('sort_order')->default(0);

            // true = tampil di halaman Program
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programs');
    }
};
