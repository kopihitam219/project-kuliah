<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coaches', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('role', 120)->nullable();       // contoh: Head Coach · Swing & Driving
            $table->string('badge', 40)->nullable();       // label kecil di foto
            $table->unsignedSmallInteger('years_experience')->nullable();
            $table->string('students', 30)->nullable();    // contoh: 450+
            $table->text('skills')->nullable();            // JSON array
            $table->string('quote', 200)->nullable();
            $table->string('photo')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Contoh awal supaya bagian About Coach langsung terlihat. Ganti dari menu admin "Kelola Coach".
        $now = now();
        DB::table('coaches')->insert([
            ['name' => 'Coach Andi', 'role' => 'Head Coach · Swing & Driving', 'badge' => 'Head Coach', 'years_experience' => 12, 'students' => '450+', 'skills' => json_encode(['Full swing', 'Driving', 'Course strategy']), 'quote' => 'Swing yang konsisten dimulai dari dasar yang benar.', 'photo' => null, 'sort_order' => 1, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Coach Budi', 'role' => 'Short Game Specialist', 'badge' => 'Short game', 'years_experience' => 9, 'students' => '300+', 'skills' => json_encode(['Chipping', 'Putting', 'Bunker']), 'quote' => 'Skor turun paling cepat dari 100 meter terakhir.', 'photo' => null, 'sort_order' => 2, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Coach Citra', 'role' => 'Junior & Beginner Coach', 'badge' => 'Junior program', 'years_experience' => 7, 'students' => '260+', 'skills' => json_encode(['Pemula', 'Junior', 'Grip & stance']), 'quote' => 'Belajar golf harus menyenangkan sejak pukulan pertama.', 'photo' => null, 'sort_order' => 3, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Coach Dimas', 'role' => 'On-Course Coach', 'badge' => 'Course lesson', 'years_experience' => 10, 'students' => '320+', 'skills' => json_encode(['Course lesson', 'Mental game', 'Etiket']), 'quote' => 'Latihan di lapangan mengajarkan cara berpikir, bukan hanya memukul.', 'photo' => null, 'sort_order' => 4, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('coaches');
    }
};
