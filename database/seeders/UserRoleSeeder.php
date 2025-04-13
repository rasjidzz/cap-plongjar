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
    public function run(): void
    {
        $now = now();

        DB::table('user__roles')->insert([
            [
                'id' => 1,
                'user_id' => 1,
                'role_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 2,
                'user_id' => 2,
                'role_id' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 3,
                'user_id' => 3,
                'role_id' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 4,
                'user_id' => 4,
                'role_id' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 5,
                'user_id' => 5,
                'role_id' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 6,
                'user_id' => 5,
                'role_id' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}
