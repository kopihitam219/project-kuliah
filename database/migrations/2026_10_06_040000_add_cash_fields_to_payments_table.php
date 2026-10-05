<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pembayaran cash: siapa yang menerima uang & catatan admin.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'received_by')) {
                $table->string('received_by', 100)->nullable()->after('paid_at');
            }

            if (! Schema::hasColumn('payments', 'payment_note')) {
                $table->string('payment_note', 255)->nullable()->after('received_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            foreach (['payment_note', 'received_by'] as $column) {
                if (Schema::hasColumn('payments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
