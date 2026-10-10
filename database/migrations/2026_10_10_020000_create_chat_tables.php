<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pengumuman (broadcast) admin ke semua member
        Schema::create('chat_broadcasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 120);
            $table->text('body');
            $table->unsignedInteger('recipients')->default(0);
            $table->timestamps();
        });

        // Satu percakapan per member (member <-> admin)
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('sender_role', 10);            // admin | member
            $table->text('body');
            $table->foreignId('broadcast_id')->nullable()->constrained('chat_broadcasts')->nullOnDelete();
            $table->timestamp('read_at')->nullable();      // dibaca oleh penerima
            $table->timestamps();

            $table->index(['member_id', 'id']);
            $table->index(['sender_role', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_broadcasts');
    }
};
