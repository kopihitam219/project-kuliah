<?php

namespace Database\Seeders;

use App\Models\ContactLocation;
use App\Models\ContactSetting;
use Illuminate\Database\Seeder;

class ContactSeeder extends Seeder
{
    /**
     * Memindahkan data kontak yang dulu hardcode ke database.
     * Hanya mengisi jika tabel masih kosong.
     */
    public function run(): void
    {
        ContactSetting::current();

        if (ContactLocation::exists()) {
            return;
        }

        ContactLocation::create([
            'name'       => 'Driving Range Rawamangun',
            'area'       => 'Rawamangun, Jakarta Timur',
            'note'       => 'Cocok untuk lesson pagi dan sore di area Jakarta.',
            'maps_query' => 'Driving Range Rawamangun Jakarta',
            'sort_order' => 1,
        ]);

        ContactLocation::create([
            'name'       => 'Driving Range Suvarna',
            'area'       => 'Suvarna, Tangerang',
            'note'       => 'Area latihan luas untuk sesi full swing dan short game.',
            'maps_query' => 'Driving Range Suvarna',
            'sort_order' => 2,
        ]);
    }
}
