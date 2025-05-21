<?php

namespace Database\Seeders;

use App\Models\ProgramStudi;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProgramStudiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            ['nama' => 'S1 Informatika'],
            ['nama' => 'S1 Rekayasa Perangkat Lunak'],
            ['nama' => 'S1 Data Sains'],
            ['nama' => 'S1 Information Technology'],
        ];

        foreach ($data as $prodi) {
            ProgramStudi::create($prodi);
        }
    }
}
