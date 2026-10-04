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
        Schema::create('galleries', function (Blueprint $table) {
            $table->id();

            // Informasi media
            $table->string('title');
            $table->text('description')->nullable();

            // image atau video
            $table->enum('type', ['image', 'video']);

            // File foto / thumbnail video
            $table->string('image')->nullable();

            // URL video, misalnya YouTube
            $table->string('video_url')->nullable();

            // Kategori gallery
            $table->string('category')->nullable();

            // active = tampil di Visitor/Member
            // inactive = tidak tampil
            $table->enum('status', ['active', 'inactive'])
                ->default('active');

            // Urutan tampilan
            $table->unsignedInteger('sort_order')
                ->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('galleries');
    }
};