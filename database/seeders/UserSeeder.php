<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Super Admin',
            'email' => 's_admin@example.com',
            'password' => Hash::make('password'), // jangan lupa hash!
        ]);
        User::create([
            'name' => 'Admin KK',
            'email' => 'admin_k@example.com',
            'password' => Hash::make('password'), // jangan lupa hash!
        ]);
        User::create([
            'name' => 'Ketua KK',
            'email' => 'ketua_kk@example.com',
            'password' => Hash::make('password'), // jangan lupa hash!
        ]);
        User::create([
            'name' => 'Admin Prodi',
            'email' => 'admin_prodi@example.com',
            'password' => Hash::make('password'), // jangan lupa hash!
        ]);
        User::create([
            'name' => 'Ketua Prodi',
            'email' => 'ketua_prodi@example.com',
            'password' => Hash::make('password'), // jangan lupa hash!
        ]);
        User::create([
            'name' => 'Kaur Lab',
            'email' => 'kaur_labif@example.com',
            'password' => Hash::make('password'), // jangan lupa hash!
        ]);
    }
}
