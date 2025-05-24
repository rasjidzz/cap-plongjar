<?php

namespace Database\Seeders;

use App\Models\JabatanStruktural;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class JabatanStrukturalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Level 1 (4 SKS)
        JabatanStruktural::updateOrCreate(
            ['nama' => 'Kepala Urusan (Direktorat)'],
            ['konversi_sks' => 4]
        );
        JabatanStruktural::updateOrCreate(
            ['nama' => 'Ketua KK'], // Kelompok Keahlian / Bidang Minat
            ['konversi_sks' => 4]
        );
        JabatanStruktural::updateOrCreate(
            ['nama' => 'Sekretaris Program Studi (Fakultas)'],
            ['konversi_sks' => 4]
        );

        // Level 2 (6 SKS)
        JabatanStruktural::updateOrCreate(
            ['nama' => 'Kepala Bagian'],
            ['konversi_sks' => 6]
        );
        JabatanStruktural::updateOrCreate(
            ['nama' => 'Kepala Program Studi'],
            ['konversi_sks' => 6]
        );

        // Level 3 (8 SKS)
        JabatanStruktural::updateOrCreate(
            ['nama' => 'Direktur'],
            ['konversi_sks' => 8]
        );
        JabatanStruktural::updateOrCreate(
            ['nama' => 'Wakil Direktur'],
            ['konversi_sks' => 8]
        );
        JabatanStruktural::updateOrCreate(
            ['nama' => 'Wakil Dekan'],
            ['konversi_sks' => 8]
        );
        JabatanStruktural::updateOrCreate(
            ['nama' => 'Dekan'],
            ['konversi_sks' => 8]
        );

        // Level 4 (10 SKS)
        JabatanStruktural::updateOrCreate(
            ['nama' => 'Wakil Rektor I'],
            ['konversi_sks' => 10]
        );
        JabatanStruktural::updateOrCreate(
            ['nama' => 'Wakil Rektor II'],
            ['konversi_sks' => 10]
        );
        JabatanStruktural::updateOrCreate(
            ['nama' => 'Wakil Rektor III'],
            ['konversi_sks' => 10]
        );
        JabatanStruktural::updateOrCreate(
            ['nama' => 'Wakil Rektor IV'],
            ['konversi_sks' => 10]
        );
        JabatanStruktural::updateOrCreate(
            ['nama' => 'Rektor'],
            ['konversi_sks' => 12]
        );
    }
}
