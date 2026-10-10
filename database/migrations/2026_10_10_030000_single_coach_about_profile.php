<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * About Coach: hanya 1 coach dengan profil lengkap (bio, pengalaman, sertifikasi, prestasi).
 * Contoh coach tambahan (Budi/Citra/Dimas) yang belum pernah diubah dihapus.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coaches', function (Blueprint $table) {
            $table->text('bio')->nullable();
            $table->text('experiences')->nullable();     // JSON [{period, text}]
            $table->text('certifications')->nullable();  // JSON array
            $table->text('achievements')->nullable();    // JSON array
        });

        DB::table('coaches')
            ->whereIn('name', ['Coach Budi', 'Coach Citra', 'Coach Dimas'])
            ->whereNull('photo')
            ->whereColumn('created_at', 'updated_at')
            ->delete();

        $main = DB::table('coaches')->orderBy('sort_order')->orderBy('id')->first();

        if (! $main) {
            DB::table('coaches')->insert([
                'name' => 'Coach Andi', 'role' => 'Head Coach · Swing & Driving', 'badge' => 'Head Coach',
                'years_experience' => 12, 'students' => '450+', 'skills' => json_encode(['Full swing', 'Driving', 'Course strategy']),
                'quote' => 'Swing yang konsisten dimulai dari dasar yang benar.', 'sort_order' => 1, 'is_active' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $main = DB::table('coaches')->orderBy('id')->first();
        }

        if ($main && $main->bio === null) {
            DB::table('coaches')->where('id', $main->id)->update([
                'bio' => 'Mulai bermain golf sejak usia 14 tahun dan aktif melatih sejak 2013. Fokus pada dasar swing yang benar, '
                    . 'konsistensi pukulan, dan strategi bermain di lapangan. Cocok untuk pemula yang baru memegang stik '
                    . 'sampai pemain yang ingin menurunkan handicap.',
                'experiences' => json_encode([
                    ['period' => '2020 – sekarang', 'text' => 'Head Coach Golf Booking Lesson'],
                    ['period' => '2016 – 2020', 'text' => 'Coach di driving range & golf club'],
                    ['period' => '2013 – 2016', 'text' => 'Asisten coach program junior'],
                ]),
                'certifications' => json_encode(['Sertifikat Pelatih Golf Nasional', 'Lisensi Coach Level 2', 'First Aid & CPR']),
                'achievements' => json_encode(['Juara 1 Amateur Open 2012', 'Melatih 450+ murid', '20+ murid turun handicap di bawah 18']),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('coaches', function (Blueprint $table) {
            $table->dropColumn(['bio', 'experiences', 'certifications', 'achievements']);
        });
    }
};
