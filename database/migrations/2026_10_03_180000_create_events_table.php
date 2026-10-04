<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('events')) {
            return;
        }

        Schema::create('events', function (Blueprint $table) {
            $table->id();

            // Informasi event
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->string('poster')->nullable();

            // Jadwal & lokasi
            $table->date('event_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('location', 150)->nullable();

            // open = Registrasi Dibuka, closed = Registrasi Ditutup, full = Kuota Penuh
            $table->enum('registration_status', ['open', 'closed', 'full'])->default('open');

            // Harga (0 = gratis)
            $table->unsignedInteger('price')->default(0);
            $table->string('price_unit', 30)->default('person');

            $table->text('note')->nullable();

            // true = tampil di halaman Event, false = disembunyikan
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
