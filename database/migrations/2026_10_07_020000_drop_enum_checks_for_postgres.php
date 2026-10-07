<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * PostgreSQL: hapus batasan nilai enum lama (mis. bookings_status_check)
 * karena status baru seperti cancelled, cash, verifying ditambahkan belakangan.
 * Nilai tetap divalidasi oleh aplikasi.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $checks = DB::select("select conrelid::regclass::text as tbl, conname from pg_constraint where contype = 'c' and connamespace = 'public'::regnamespace");

        foreach ($checks as $check) {
            DB::statement('alter table ' . $check->tbl . ' drop constraint if exists "' . $check->conname . '"');
        }
    }

    public function down(): void
    {
        //
    }
};