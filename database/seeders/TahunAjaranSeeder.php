<?php

namespace Database\Seeders;

use App\Models\TahunAjaran;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TahunAjaranSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TahunAjaran::create([
            'tahun_ajaran' => '2024/2025',
            'semester' => 'ganjil',
        ]);

        TahunAjaran::create([
            'tahun_ajaran' => '2024/2025',
            'semester' => 'genap',
        ]);

        TahunAjaran::factory(5)->create();
    }
}
