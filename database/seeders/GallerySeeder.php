<?php

namespace Database\Seeders;

use App\Models\Gallery;
use Illuminate\Database\Seeder;

class GallerySeeder extends Seeder
{
    /**
     * Memindahkan isi galeri yang dulu hardcode ke database.
     * Hanya berjalan jika tabel galleries masih kosong.
     */
    public function run(): void
    {
        if (Gallery::exists()) {
            return;
        }

        $photos = [
            ['title' => 'Golf Training', 'category' => 'Practice & Training'],
            ['title' => 'Golf Course',   'category' => 'On Course Experience'],
            ['title' => 'Golf Swing',    'category' => 'Swing Development'],
        ];

        foreach ($photos as $index => $photo) {
            Gallery::create($photo + [
                'type'       => 'image',
                'image'      => 'images/background.golf.jpeg',
                'status'     => 'active',
                'sort_order' => $index + 1,
            ]);
        }

        $videos = [
            [
                'title'       => 'Golf Swing Basics - Easy Steps For Beginners',
                'category'    => 'Beginner',
                'description' => 'Video rekomendasi untuk memahami dasar golf swing bagi pemain pemula.',
                'video_url'   => 'https://www.youtube.com/watch?v=aIB8BnsrV3M',
            ],
            [
                'title'       => 'Simple Golf Swing Lesson',
                'category'    => 'Beginner',
                'description' => 'Fundamental swing motion dan latihan dasar untuk golfer pemula.',
                'video_url'   => 'https://www.youtube.com/watch?v=QCKTR1fd-6c',
            ],
            [
                'title'       => 'Beginner Golf Full Swing Lesson',
                'category'    => 'Golf Lesson',
                'description' => 'Mengenal grip, stance, setup, dan dasar full swing.',
                'video_url'   => 'https://www.youtube.com/watch?v=pHojpi8ecrs',
            ],
            [
                'title'       => 'On Course Strategy Lesson',
                'category'    => 'Course Strategy',
                'description' => 'Mengenal strategi bermain dan course management di golf course.',
                'video_url'   => 'https://www.youtube.com/watch?v=VdCojtbZqdY',
            ],
        ];

        foreach ($videos as $index => $video) {
            Gallery::create($video + [
                'type'       => 'video',
                'status'     => 'active',
                'sort_order' => $index + 1,
            ]);
        }
    }
}
