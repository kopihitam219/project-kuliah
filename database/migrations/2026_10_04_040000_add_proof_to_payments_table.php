<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bukti pembayaran (screenshot transfer / QRIS) yang di-upload customer.
     * File disimpan di storage privat, bukan folder publik.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'proof_path')) {
                $table->string('proof_path')->nullable()->after('va_number');
            }

            if (! Schema::hasColumn('payments', 'proof_uploaded_at')) {
                $table->timestamp('proof_uploaded_at')->nullable()->after('proof_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            foreach (['proof_uploaded_at', 'proof_path'] as $column) {
                if (Schema::hasColumn('payments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
