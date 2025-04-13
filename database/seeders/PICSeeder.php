<?php

namespace Database\Seeders;

use App\Models\Pic;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PICSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Pic::create(
            [
                'name' => 'BPA'
            ]
        );
        Pic::create(
            [
                'name' => 'S1 Informatika'
            ]
        );
        Pic::create(
            [
                'name' => 'S1 Rekayasa Perangkat Lunak'
            ]
        );
        Pic::create(
            [
                'name' => 'S1 Data Sains'
            ]
        );
        Pic::create(
            [
                'name' => 'S1 Informasi Teknologi'
            ]
        );
        Pic::create(
            [
                'name' => 'SEAL'
            ]
        );
        Pic::create(
            [
                'name' => 'CITI'
            ]
        );
        Pic::create(
            [
                'name' => 'DSIS'
            ]
        );
    }
}
