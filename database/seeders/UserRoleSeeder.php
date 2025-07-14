<?php

namespace Database\Seeders;

use App\Models\User_Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    // public function run(): void
    // {
    //     $now = now();

    //     DB::table('user_roles')->insert([
    //         [
    //             'id' => 1,
    //             'user_id' => 1,
    //             'role_id' => 1,
    //             'created_at' => $now,
    //             'updated_at' => $now,
    //         ],
    //         // Anisa Admin KK Citi
    //         [
    //             'id' => 2,
    //             'user_id' => 2,
    //             'role_id' => 3,
    //             'roleable_id' => 3,
    //             'roleable_type' => "App\\Models\\KelompokKeahlian",
    //             'created_at' => $now,
    //             'updated_at' => $now,
    //         ],
    //         // Amelia Ketua KK Citi
    //         [
    //             'id' => 3,
    //             'user_id' => 3,
    //             'role_id' => 3,
    //             'roleable_id' => 3,
    //             'roleable_type' => "App\\Models\\KelompokKeahlian",
    //             'created_at' => $now,
    //             'updated_at' => $now,
    //         ],
    //         // Asep admin prodi RPL
    //         [
    //             'id' => 4,
    //             'user_id' => 4,
    //             'role_id' => 2,
    //             'roleable_id' => 2,
    //             'roleable_type' => "App\\Models\\ProgramStudi",
    //             'created_at' => $now,
    //             'updated_at' => $now,
    //         ],
    //         // Bambang kaprodi RPL
    //         [
    //             'id' => 5,
    //             'user_id' => 5,
    //             'role_id' => 2,
    //             'roleable_id' => 2,
    //             'roleable_type' => "App\\Models\\ProgramStudi",
    //             'created_at' => $now,
    //             'updated_at' => $now,
    //         ],
    //         // Ovi Kaur Lab IF
    //         [
    //             'id' => 6,
    //             'user_id' => 5,
    //             'role_id' => 5,
    //             'created_at' => $now,
    //             'updated_at' => $now,
    //         ],
    //     ]);
    // }
    public function run(): void
    {
        $now = now();

        // Super Admin
        User_Role::create([
            'user_id' => 1,
            'role_id' => 1,
            'roleable_id' => null,
            'roleable_type' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Anisa - Admin KK CITI
        User_Role::create([
            'user_id' => 2,
            'role_id' => 3,
            'roleable_id' => 3,
            'roleable_type' => "App\\Models\\KelompokKeahlian",
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Amelia - Ketua KK CITI
        User_Role::create([
            'user_id' => 3,
            'role_id' => 3,
            'roleable_id' => 3,
            'roleable_type' => "App\\Models\\KelompokKeahlian",
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Asep - Admin Prodi RPL
        User_Role::create([
            'user_id' => 4,
            'role_id' => 2,
            'roleable_id' => 2,
            'roleable_type' => "App\\Models\\ProgramStudi",
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Bambang - Kaprodi RPL
        User_Role::create([
            'user_id' => 5,
            'role_id' => 2,
            'roleable_id' => 2,
            'roleable_type' => "App\\Models\\ProgramStudi",
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Ovi - Kaur Lab (no roleable)
        User_Role::create([
            'user_id' => 6,
            'role_id' => 5,
            'roleable_id' => null,
            'roleable_type' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Owala - Admin Prodi Informatika
        User_Role::create([
            'user_id' => 7,
            'role_id' => 2,
            'roleable_id' => 1,
            'roleable_type' => "App\\Models\\ProgramStudi",
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Admin Prodi DS
        User_Role::create([
            'user_id' => 8,
            'role_id' => 2,
            'roleable_id' => 3,
            'roleable_type' => "App\\Models\\ProgramStudi",
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Admin Prodi IT
        User_Role::create([
            'user_id' => 9,
            'role_id' => 2,
            'roleable_id' => 3,
            'roleable_type' => "App\\Models\\ProgramStudi",
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Admin KK SEAL
        User_Role::create([
            'user_id' => 10,
            'role_id' => 3,
            'roleable_id' => 1,
            'roleable_type' => "App\\Models\\KelompokKeahlian",
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Admin KK DSIS
        User_Role::create([
            'user_id' => 11,
            'role_id' => 3,
            'roleable_id' => 2,
            'roleable_type' => "App\\Models\\KelompokKeahlian",
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Admin LAK
        User_Role::create([
            'user_id' => 12,
            'role_id' => 4,
            'roleable_id' => null,
            'roleable_type' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $programStudiIds = [1, 2, 3];
        $kelompokKeahlianIds = [1, 2, 3];

        for ($userId = 13; $userId <= 52; $userId++) {
            $roleId = rand(2, 5);

            if (in_array($roleId, [2, 4])) {
                $roleableType = "App\\Models\\ProgramStudi";
                $roleableId = collect($programStudiIds)->random();
            } else {
                $roleableType = "App\\Models\\KelompokKeahlian";
                $roleableId = collect($kelompokKeahlianIds)->random();
            }

            User_Role::create([
                'user_id' => $userId,
                'role_id' => $roleId,
                'roleable_id' => $roleableId,
                'roleable_type' => $roleableType,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
