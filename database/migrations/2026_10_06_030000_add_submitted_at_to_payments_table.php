<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Waktu customer mengirim / mengonfirmasi pembayaran.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('expires_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'submitted_at')) {
                $table->dropColumn('submitted_at');
            }
        });
    }
};
