<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Informasi kontak umum (hanya 1 baris)
        if (! Schema::hasTable('contact_settings')) {
            Schema::create('contact_settings', function (Blueprint $table) {
                $table->id();
                $table->text('description')->nullable();
                $table->string('whatsapp', 30)->nullable();
                $table->string('email')->nullable();
                $table->string('opening_hours', 100)->nullable();
                $table->timestamps();
            });
        }

        // Lokasi latihan (bisa lebih dari satu)
        if (! Schema::hasTable('contact_locations')) {
            Schema::create('contact_locations', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->string('area', 100)->nullable();
                $table->string('note')->nullable();
                $table->string('maps_query')->nullable(); // kata kunci / alamat untuk Google Maps
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_locations');
        Schema::dropIfExists('contact_settings');
    }
};
