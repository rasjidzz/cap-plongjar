<?php

namespace Database\Seeders;

use App\Models\KelompokKeahlian;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class KelompokKeahlianSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            'Software Engineering and Algorithm',
            'Data Science and Intelligent System',
            'Communication and Information Technology Infrastructure',
            'Processing, Information Security, and Computer Engineering',
            'Applied Digital Business, Entrepreneurship & Tourism',
        ];

        foreach ($data as $nama) {
            KelompokKeahlian::create([
                'nama' => $nama,
            ]);
        }
    }
}
