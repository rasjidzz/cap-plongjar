<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\User_Role;
use App\Models\ProgramStudi; // Diasumsikan ada model ini jika roleable_type adalah ProgramStudi
use App\Models\KelompokKeahlian; // Diasumsikan ada model ini jika roleable_type adalah KelompokKeahlian
use Illuminate\Support\Facades\Hash;

class RoleTest extends TestCase
{
    use RefreshDatabase; // Memastikan database bersih untuk setiap test
    use WithFaker;     // Untuk membuat data dummy

    /**
     * Setup the test environment.
     * Membuat data awal yang dibutuhkan sebelum setiap test dijalankan.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Buat role dasar jika belum ada
        Role::firstOrCreate(['id' => 1, 'name' => 'Superadmin']);
        Role::firstOrCreate(['id' => 2, 'name' => 'ProgramStudi']);
        Role::firstOrCreate(['id' => 3, 'name' => 'KelompokKeahlian']);
        Role::firstOrCreate(['id' => 4, 'name' => 'LayananAkademik']);
        Role::firstOrCreate(['id' => 5, 'name' => 'KepalaUrusanLab']);
    }

    /**
     * Helper method untuk membuat user admin yang bisa melakukan aksi.
     *
     * @return \App\Models\User
     */
    protected function createAdminUser()
    {
        $admin = User::factory()->create();
        $adminRole = Role::where('name', 'Superadmin')->first();
        User_Role::create([
            'user_id' => $admin->id,
            'role_id' => $adminRole->id,
            'roleable_type' => null,
            'roleable_id' => null,
        ]);
        return $admin;
    }

    public function test_admin_can_assign_role_successfully(): void
    {
        $admin = $this->createAdminUser();
        $userToAssign = User::factory()->create();
        $targetRole = Role::where('name', 'ProgramStudi')->first();
        $programStudi = ProgramStudi::factory()->create(['nama' => 'S1 Informatika']);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/roles/assignRole', [
            'user_id' => $userToAssign->id,
            'role_id' => $targetRole->id,
            'roleable_id' => $programStudi->id,
            'roleable_type' => ProgramStudi::class,
        ]);

        // 3. Assertions
        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'User Assigned to Role Successfully'
                 ]);

    }

    public function test_admin_cannot_assign_role_if_user_not_found(): void
    {
        $admin = $this->createAdminUser();
        $targetRole = Role::where('name', 'Superadmin')->first();

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/roles/assignRole', [
            'user_id' => 9999,
            'role_id' => $targetRole->id,
            'roleable_id' => null,
            'roleable_type' => null,
        ]);

        $response->assertStatus(401)
                 ->assertJson([
                     'message' => 'User not Found'
                 ]);
    }

    /**
     * TC-ADM-05: Admin gagal memberikan hak akses (Role not found).
     * HTTP Response status code = 401 (Role not found)
     *
     * @return void
     */
    public function test_admin_cannot_assign_role_if_role_not_found(): void
    {
        // 1. Persiapan Data
        $admin = $this->createAdminUser();
        $userToAssign = User::factory()->create();

        // 2. Aksi: Login sebagai admin dan kirim POST request dengan role_id yang tidak ada
        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/roles/assignRole', [
            'user_id' => $userToAssign->id,
            'role_id' => 9999, // Role ID yang tidak ada
            'roleable_id' => null,
            'roleable_type' => null,
        ]);

        // 3. Assertions
        $response->assertStatus(401) // Memastikan status HTTP 401
                 ->assertJson([
                     'message' => 'Role not Found'
                 ]);

        // Pastikan tidak ada entri baru di database
        $this->assertDatabaseMissing('user_roles', [
            'user_id' => $userToAssign->id,
            'role_id' => 9999,
        ]);
    }

    /**
     * TC-ADM-06: Admin gagal memberikan hak akses (User already has this role assigned).
     * HTTP Response status code = 409 (User already has this role assigned)
     *
     * @return void
     */
    public function test_admin_cannot_assign_role_if_already_assigned(): void
    {
        // 1. Persiapan Data
        $admin = $this->createAdminUser();
        $userToAssign = User::factory()->create();
        $targetRole = Role::where('name', 'Superadmin')->first(); // Contoh role yang sama

        // Assign role terlebih dahulu
        User_Role::create([
            'user_id' => $userToAssign->id,
            'role_id' => $targetRole->id,
            'roleable_id' => null,
            'roleable_type' => null,
        ]);

        // 2. Aksi: Login sebagai admin dan coba assign role yang sama lagi
        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/roles/assignRole', [
            'user_id' => $userToAssign->id,
            'role_id' => $targetRole->id,
            'roleable_id' => null,
            'roleable_type' => null,
        ]);

        // 3. Assertions
        $response->assertStatus(409) // Memastikan status HTTP 409 (Conflict)
                 ->assertJson([
                     'message' => 'User already has this role assigned'
                 ]);

        // Opsional: Verifikasi bahwa tidak ada duplikasi entri di database
        // Hitung jumlah entri dengan user_id dan role_id yang sama
        $count = User_Role::where('user_id', $userToAssign->id)
                          ->where('role_id', $targetRole->id)
                          ->whereNull('roleable_id') // Jika roleable_id null
                          ->whereNull('roleable_type') // Jika roleable_type null
                          ->count();
        $this->assertEquals(1, $count); // Hanya boleh ada satu entri
    }

    public function test_admin_can_revoke_user_role_successfully(): void
    {
        $admin = $this->createAdminUser();
        $userWithRole = User::factory()->create();
        $roleToRevoke = Role::where('name', 'ProgramStudi')->first();

        User_Role::create([
            'user_id' => $userWithRole->id,
            'role_id' => $roleToRevoke->id,
            'roleable_type' => null,
            'roleable_id' => null,
        ]);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/roles/revokeRole', [
            'user_id' => $userWithRole->id,
            'role_id' => $roleToRevoke->id,
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Role revoked successfully from user'
                 ]);

    }

    public function test_admin_cannot_revoke_non_existent_user_role(): void
    {
        $admin = $this->createAdminUser();
        $userWithoutRole = User::factory()->create();
        $roleNotAssigned = Role::where('name', 'LayananAkademik')->first();

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/roles/revokeRole', [
            'user_id' => $userWithoutRole->id,
            'role_id' => $roleNotAssigned->id,
        ]);

        $response->assertStatus(404)
                 ->assertJson([
                     'message' => 'Role not found for this user'
                 ]);
    }

     public function test_admin_can_view_all_assigned_user_roles(): void
    {
        // 1. Persiapan Data
        $admin = $this->createAdminUser(); // Ini membuat 1 User_Role

        // Buat beberapa user dan assign beberapa role
        $user1 = User::factory()->create(['name' => 'User One']);
        $user2 = User::factory()->create(['name' => 'User Two']);
        $roleProgramStudi = Role::where('name', 'ProgramStudi')->first();
        $roleKelompokKeahlian = Role::where('name', 'KelompokKeahlian')->first();

        $prodi1 = ProgramStudi::factory()->create(['nama' => 'S1 Informatika']);
        $kk1 = KelompokKeahlian::factory()->create(['nama' => 'SEAL']);

        User_Role::create([
            'user_id' => $user1->id,
            'role_id' => $roleProgramStudi->id,
            'roleable_id' => $prodi1->id,
            'roleable_type' => ProgramStudi::class,
        ]); // Ini User_Role ke-2

        User_Role::create([
            'user_id' => $user2->id,
            'role_id' => $roleKelompokKeahlian->id,
            'roleable_id' => $kk1->id,
            'roleable_type' => KelompokKeahlian::class,
        ]); // Ini User_Role ke-3

        User_Role::create([
            'user_id' => $user2->id,
            'role_id' => Role::where('name', 'LayananAkademik')->first()->id, // Role tanpa roleable
            'roleable_id' => null,
            'roleable_type' => null,
        ]); // Ini User_Role ke-4


        // 2. Aksi: Login sebagai admin dan kirim GET request untuk melihat semua role
        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/roles');

        // 3. Assertions
        $response->assertStatus(200) // Memastikan status HTTP 200
                 ->assertJsonCount(5); // <-- UBAH DARI 3 MENJADI 4
    }

    public function test_admin_can_assign_scoped_role_successfully(): void
    {
        $admin = $this->createAdminUser();
        $userToAssign = User::factory()->create();
        $roleProgramStudi = Role::where('name', 'ProgramStudi')->first();
        $programStudi = ProgramStudi::factory()->create(['nama' => 'S1 Rekayasa Perangkat Lunak']);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/roles/assign-scoped-role', [
            'user_id' => $userToAssign->id,
            'role_id' => $roleProgramStudi->id,
            'roleable_id' => $programStudi->id,
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Role berhasil di-assign ke user.',
                 ]);
    }

    public function test_admin_cannot_assign_scoped_role_with_invalid_id(): void
    {
        // 1. Persiapan Data
        $admin = $this->createAdminUser();
        $roleProgramStudi = Role::where('name', 'ProgramStudi')->first();

        // Skenario 1: user_id tidak valid
        $response1 = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/roles/assign-scoped-role', [
            'user_id' => 9999, // User ID tidak ada
            'role_id' => $roleProgramStudi->id,
            'roleable_id' => 1, // Asumsi ada ProgramStudi dengan ID 1
        ]);
        $response1->assertStatus(422)
                  ->assertJsonValidationErrors(['user_id']);

        // Skenario 2: role_id tidak valid (bukan 2 atau 3)
        $userToAssign = User::factory()->create();
        $roleLayananAkademik = Role::where('name', 'LayananAkademik')->first(); // ID 4
        $response2 = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/roles/assign-scoped-role', [
            'user_id' => $userToAssign->id,
            'role_id' => $roleLayananAkademik->id, // ID 4, tidak diizinkan
            'roleable_id' => 1,
        ]);
        $response2->assertStatus(422)
                  ->assertJsonValidationErrors(['role_id']);

        // Skenario 3: roleable_id tidak valid untuk role ProgramStudi
        $programStudiNonExistent = 9999;
        $response3 = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/roles/assign-scoped-role', [
            'user_id' => $userToAssign->id,
            'role_id' => $roleProgramStudi->id,
            'roleable_id' => $programStudiNonExistent, // ID ProgramStudi tidak ada
        ]);
        $response3->assertStatus(422) // Controller mengembalikan 422 jika roleable_exists false
                  ->assertJson([
                      'success' => false,
                      'message' => 'Data entitas (Program Studi/Kelompok Keahlian) dengan ID yang diberikan tidak ditemukan.',
                  ])
                  ->assertJsonValidationErrors(['roleable_id']);

        // Skenario 4: roleable_id tidak valid untuk role KelompokKeahlian
        $roleKelompokKeahlian = Role::where('name', 'KelompokKeahlian')->first();
        $kelompokKeahlianNonExistent = 8888;
        $response4 = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/roles/assign-scoped-role', [
            'user_id' => $userToAssign->id,
            'role_id' => $roleKelompokKeahlian->id,
            'roleable_id' => $kelompokKeahlianNonExistent, // ID KelompokKeahlian tidak ada
        ]);
        $response4->assertStatus(422)
                  ->assertJson([
                      'success' => false,
                      'message' => 'Data entitas (Program Studi/Kelompok Keahlian) dengan ID yang diberikan tidak ditemukan.',
                  ])
                  ->assertJsonValidationErrors(['roleable_id']);
    }

}
