<?php

namespace Database\Seeders;

use App\Models\Program;
use Illuminate\Database\Seeder;

class ProgramSeeder extends Seeder
{
    /**
     * Memindahkan 4 program yang dulu hardcode ke database.
     * Hanya berjalan jika tabel programs masih kosong.
     */
    public function run(): void
    {
        if (Program::exists()) {
            return;
        }

        $programs = [
            [
                'level'          => 'BEGINNER',
                'name'           => 'Beginner Program',
                'description'    => 'Bangun dasar permainan golf yang kuat dan percaya diri.',
                'features'       => ['Fundamental Swing', 'Putting', 'Short Game'],
                'image_position' => 'left',
            ],
            [
                'level'          => 'INTERMEDIATE',
                'name'           => 'Intermediate Program',
                'description'    => 'Tingkatkan konsistensi swing dan strategi permainan di lapangan.',
                'features'       => ['Swing Improvement', 'Course Strategy', 'Mental Game'],
                'image_position' => 'center-left',
            ],
            [
                'level'          => 'ADVANCED',
                'name'           => 'Advanced Program',
                'description'    => 'Optimalkan teknik, kondisi fisik, dan performa permainan.',
                'features'       => ['Advanced Technique', 'Physical Conditioning', 'Game Analysis'],
                'image_position' => 'center-right',
            ],
            [
                'level'          => 'TOURNAMENT',
                'name'           => 'Tournament Preparation',
                'description'    => 'Persiapkan permainan untuk menghadapi kompetisi dengan lebih siap.',
                'features'       => ['Tournament Strategy', 'Pressure Handling', 'On-Course Training'],
                'image_position' => 'right',
            ],
        ];

        foreach ($programs as $index => $program) {
            Program::create($program + [
                'image'      => null, // pakai gambar bawaan
                'sort_order' => $index + 1,
                'is_active'  => true,
            ]);
        }
    }
}
