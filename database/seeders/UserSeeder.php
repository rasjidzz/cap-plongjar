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
        // $generateNip = fn() => \fake()->numerify('##################'); // 18 digit

        // User::create([
        //     'name' => 'Super Admin',
        //     'email' => 's_admin@example.com',
        //     'password' => Hash::make('password'),
        //     'nip' => $generateNip(),
        // ]);
        // User::create([
        //     'name' => 'Anisa Admin KK Citi',
        //     'email' => 'anisa_admin_kk_citi@example.com',
        //     'password' => Hash::make('password'),
        //     'nip' => $generateNip(),
        // ]);
        // User::create([
        //     'name' => 'Amelia Ketua KK Citi',
        //     'email' => 'amelia_ketua_kk_citi@example.com',
        //     'password' => Hash::make('password'),
        //     'nip' => $generateNip(),
        // ]);
        // User::create([
        //     'name' => 'Asep Admin Prodi S1 RPL',
        //     'email' => 'asep_admin_prodi_rpl@example.com',
        //     'password' => Hash::make('password'),
        //     'nip' => $generateNip(),
        // ]);
        // User::create([
        //     'name' => 'Bambang Ketua Prodi S1 RPL',
        //     'email' => 'bambang_ketua_prodi_rpl@example.com',
        //     'password' => Hash::make('password'),
        //     'nip' => $generateNip(),
        // ]);
        // User::create([
        //     'name' => 'Ovi Rangkuti Kaur Lab Informatics',
        //     'email' => 'ovi_kaur_labif@example.com',
        //     'password' => Hash::make('password'),
        //     'nip' => $generateNip(),
        // ]);
        // User::create([
        //     'name' => 'Owala Asam Admin Prodi S1 Informatika',
        //     'email' => 'owalainformatika@example.com',
        //     'password' => Hash::make('password'),
        //     'nip' => $generateNip(),
        // ]);
        // User::create([
        //     'name' => 'Admin Prodi S1 Data Science',
        //     'email' => 'adminds@example.com',
        //     'password' => Hash::make('password'),
        //     'nip' => $generateNip(),
        // ]);
        // User::create([
        //     'name' => 'Admin Prodi S1 Information Technology',
        //     'email' => 'adminit@example.com',
        //     'password' => Hash::make('password'),
        //     'nip' => $generateNip(),
        // ]);
        // User::create([
        //     'name' => 'Admin KK SEAL',
        //     'email' => 'adminseal@example.com',
        //     'password' => Hash::make('password'),
        //     'nip' => $generateNip(),
        // ]);
        // User::create(attributes: [
        //     'name' => 'Admin KK DSIS',
        //     'email' => 'admindsis@example.com',
        //     'password' => Hash::make('password'),
        //     'nip' => $generateNip(),
        // ]);
        // User::create(attributes: [
        //     'name' => 'Admin LAK',
        //     'email' => 'admindlak@example.com',
        //     'password' => Hash::make('password'),
        //     'nip' => $generateNip(),
        // ]);
        User::create([
            'name' => 'Super Admin',
            'email' => 's_admin@example.com',
            'password' => Hash::make('password'),
            'nip' => '1980010112340001',
        ]);

        User::create([
            'name' => 'Anisa Admin KK Citi',
            'email' => 'anisa_admin_kk_citi@example.com',
            'password' => Hash::make('password'),
            'nip' => '1980010112340002',
        ]);

        User::create([
            'name' => 'Amelia Ketua KK Citi',
            'email' => 'amelia_ketua_kk_citi@example.com',
            'password' => Hash::make('password'),
            'nip' => '1980010112340003',
        ]);

        User::create([
            'name' => 'Asep Admin Prodi S1 RPL',
            'email' => 'asep_admin_prodi_rpl@example.com',
            'password' => Hash::make('password'),
            'nip' => '1980010112340004',
        ]);

        User::create([
            'name' => 'Bambang Ketua Prodi S1 RPL',
            'email' => 'bambang_ketua_prodi_rpl@example.com',
            'password' => Hash::make('password'),
            'nip' => '1980010112340005',
        ]);

        User::create([
            'name' => 'Ovi Rangkuti Kaur Lab Informatics',
            'email' => 'ovi_kaur_labif@example.com',
            'password' => Hash::make('password'),
            'nip' => '1980010112340006',
        ]);

        User::create([
            'name' => 'Owala Asam Admin Prodi S1 Informatika',
            'email' => 'owalainformatika@example.com',
            'password' => Hash::make('password'),
            'nip' => '1980010112340007',
        ]);

        User::create([
            'name' => 'Admin Prodi S1 Data Science',
            'email' => 'adminds@example.com',
            'password' => Hash::make('password'),
            'nip' => '1980010112340008',
        ]);

        User::create([
            'name' => 'Admin Prodi S1 Information Technology',
            'email' => 'adminit@example.com',
            'password' => Hash::make('password'),
            'nip' => '1980010112340009',
        ]);

        User::create([
            'name' => 'Admin KK SEAL',
            'email' => 'adminseal@example.com',
            'password' => Hash::make('password'),
            'nip' => '1980010112340010',
        ]);

        User::create([
            'name' => 'Admin KK DSIS',
            'email' => 'admindsis@example.com',
            'password' => Hash::make('password'),
            'nip' => '1980010112340011',
        ]);

        User::create([
            'name' => 'Admin LAK',
            'email' => 'admindlak@example.com',
            'password' => Hash::make('password'),
            'nip' => '1980010112340012',
        ]);
    }
}
