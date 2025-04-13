<?php

namespace Database\Seeders;

use App\Models\Matakuliah;
use App\Models\Pic;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MatakuliahSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Membuat 10 data Matakuliah secara manual
        Matakuliah::create([
            'kode_matkul' => 'MTK101',
            'sks' => 3,
            'praktikum' => true,
            'id_pic' => 1,
            'mandatory_status' => 'wajib_prodi',
            'mode_perkuliahan' => 'online',
        ]);

        Matakuliah::create([
            'kode_matkul' => 'MTK102',
            'sks' => 2,
            'praktikum' => false,
            'id_pic' => 2,
            'mandatory_status' => 'pilihan',
            'mode_perkuliahan' => 'onsite',
        ]);

        Matakuliah::create([
            'kode_matkul' => 'MTK103',
            'sks' => 4,
            'praktikum' => true,
            'id_pic' => 3,
            'mandatory_status' => 'wajib_prodi',
            'mode_perkuliahan' => 'hybrid',
        ]);

        Matakuliah::create([
            'kode_matkul' => 'MTK104',
            'sks' => 3,
            'praktikum' => false,
            'id_pic' => 4,
            'mandatory_status' => 'pilihan',
            'mode_perkuliahan' => 'online',
        ]);
    }
}
