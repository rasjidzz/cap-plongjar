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
        KelompokKeahlian::insert([
            ['nama' => 'Software Engineering and Algorithm', 'created_at' => now(), 'updated_at' => now()],
            ['nama' => 'Data Science and Intelligent System', 'created_at' => now(), 'updated_at' => now()],
            ['nama' => 'Communication and Information Technology Infrastructure', 'created_at' => now(), 'updated_at' => now()],
            ['nama' => 'Processing, Information Security, and Computer Engineering', 'created_at' => now(), 'updated_at' => now()],
            ['nama' => 'Applied Digital Business, Entrepreneurship & Tourism', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
