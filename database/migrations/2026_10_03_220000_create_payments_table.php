<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payments')) {
            return;
        }

        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('reference', 40)->unique();          // contoh: GBL-20261003-00012
            $table->unsignedInteger('duration_minutes');
            $table->unsignedInteger('amount');                   // total dalam Rupiah

            $table->string('method', 20)->nullable();            // qris | mandiri | bca
            $table->string('va_number', 30)->nullable();         // nomor VA dummy (bank)

            // unpaid = belum pilih metode, pending = menunggu bayar, paid = lunas
            $table->string('status', 20)->default('unpaid');

            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
