<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Samakan email yang sudah tersimpan jadi huruf kecil tanpa spasi,
 * supaya login dari HP (huruf kapital otomatis) tetap cocok.
 */
return new class extends Migration
{
    public function up(): void
    {
        $users = DB::table('users')->select('id', 'email')->get();
        $taken = $users->map(fn ($u) => mb_strtolower(trim((string) $u->email)))->countBy();

        foreach ($users as $user) {
            $clean = mb_strtolower(trim((string) $user->email));

            if ($clean !== $user->email && ($taken[$clean] ?? 0) === 1) {
                DB::table('users')->where('id', $user->id)->update(['email' => $clean]);
            }
        }
    }

    public function down(): void
    {
        //
    }
};
