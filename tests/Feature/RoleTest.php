<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\User_Role;
use App\Models\ProgramStudi; // Diasumsikan ada model ini jika roleable_type adalah ProgramStudi
use App\Models\KelompokKeahlian; // Diasumsikan ada model ini jika roleable_type adalah KelompokKeahlian

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

    /**
     * TC-ADM-03: Admin berhasil memberikan hak akses untuk setiap pengguna.
     * HTTP Response status code = 200 (User Assigned to Role Successfully)
     *
     * @return void
     */
    public function test_admin_can_assign_role_successfully(): void
    {
        // 1. Persiapan Data
        $admin = $this->createAdminUser();
        $userToAssign = User::factory()->create();
        $targetRole = Role::where('name', 'ProgramStudi')->first();
        $programStudi = ProgramStudi::factory()->create(['nama' => 'S1 Informatika']); // Buat ProgramStudi dummy

        // 2. Aksi: Login sebagai admin dan kirim POST request untuk assign role
        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/roles/assignRole', [
            'user_id' => $userToAssign->id,
            'role_id' => $targetRole->id,
            'roleable_id' => $programStudi->id,
            'roleable_type' => ProgramStudi::class, // Menggunakan FQCN
        ]);

        // 3. Assertions
        $response->assertStatus(200) // Memastikan status HTTP 200
                 ->assertJson([
                     'message' => 'User Assigned to Role Successfully'
                 ]);

        // Verifikasi database: memastikan role berhasil di-assign
        $this->assertDatabaseHas('user_roles', [
            'user_id' => $userToAssign->id,
            'role_id' => $targetRole->id,
            'roleable_id' => $programStudi->id,
            'roleable_type' => ProgramStudi::class,
        ]);
    }

    /**
     * TC-ADM-04: Admin gagal memberikan hak akses (User not found).
     * HTTP Response status code = 401 (User not found)
     *
     * @return void
     */
    public function test_admin_cannot_assign_role_if_user_not_found(): void
    {
        // 1. Persiapan Data
        $admin = $this->createAdminUser();
        $targetRole = Role::where('name', 'Superadmin')->first();

        // 2. Aksi: Login sebagai admin dan kirim POST request dengan user_id yang tidak ada
        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/roles/assignRole', [
            'user_id' => 9999, // User ID yang tidak ada
            'role_id' => $targetRole->id,
            'roleable_id' => null,
            'roleable_type' => null,
        ]);

        // 3. Assertions
        $response->assertStatus(401) // Memastikan status HTTP 401
                 ->assertJson([
                     'message' => 'User not Found'
                 ]);

        // Pastikan tidak ada entri baru di database
        $this->assertDatabaseMissing('user_roles', [
            'user_id' => 9999,
            'role_id' => $targetRole->id,
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

        // --- New Test cases for revokeRole ---

    /**
     * TC-ADM-07: Admin dapat menghapus role pengguna.
     * Memastikan Admin berhasil menghapus role pengguna.
     * HTTP Response status code = 200 (Success)
     *
     * @return void
     */
    public function test_admin_can_revoke_user_role_successfully(): void
    {
        // 1. Persiapan Data
        $admin = $this->createAdminUser();
        $userWithRole = User::factory()->create();
        $roleToRevoke = Role::where('name', 'ProgramStudi')->first();

        // Assign role ke user terlebih dahulu agar bisa dihapus
        User_Role::create([
            'user_id' => $userWithRole->id,
            'role_id' => $roleToRevoke->id,
            'roleable_type' => null,
            'roleable_id' => null,
        ]);

        // Pastikan role sudah ada di database sebelum dihapus
        $this->assertDatabaseHas('user_roles', [
            'user_id' => $userWithRole->id,
            'role_id' => $roleToRevoke->id,
        ]);

        // 2. Aksi: Login sebagai admin dan kirim POST request untuk revoke role
        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/roles/revokeRole', [
            'user_id' => $userWithRole->id,
            'role_id' => $roleToRevoke->id,
        ]);

        // 3. Assertions
        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Role revoked successfully from user'
                 ]);

        // Verifikasi database: memastikan role berhasil dihapus
        $this->assertDatabaseMissing('user_roles', [
            'user_id' => $userWithRole->id,
            'role_id' => $roleToRevoke->id,
        ]);
    }

    /**
     * TC-ADM-08: Admin gagal menghapus role pengguna.
     * Memastikan Admin gagal menghapus role pengguna yang tidak ditemukan.
     * HTTP Response status code = 404 (Role not found)
     *
     * @return void
     */
    public function test_admin_cannot_revoke_non_existent_user_role(): void
    {
        // 1. Persiapan Data
        $admin = $this->createAdminUser();
        $userWithoutRole = User::factory()->create();
        $roleNotAssigned = Role::where('name', 'LayananAkademik')->first();

        // Pastikan role TIDAK ada di database untuk user ini
        $this->assertDatabaseMissing('user_roles', [
            'user_id' => $userWithoutRole->id,
            'role_id' => $roleNotAssigned->id,
        ]);

        // 2. Aksi: Login sebagai admin dan kirim POST request untuk revoke role yang tidak ada
        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/roles/revokeRole', [
            'user_id' => $userWithoutRole->id,
            'role_id' => $roleNotAssigned->id,
        ]);

        // 3. Assertions
        $response->assertStatus(404)
                 ->assertJson([
                     'message' => 'Role not found for this user'
                 ]);

        // Opsional: Coba skenario dengan user_id yang tidak ada
        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/roles/revokeRole', [
            'user_id' => 9999, // User ID yang tidak ada
            'role_id' => $roleNotAssigned->id,
        ]);
        $response->assertStatus(404)
                 ->assertJson([
                     'message' => 'Role not found for this user'
                 ]);

        // Opsional: Coba skenario dengan role_id yang tidak ada
        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/roles/revokeRole', [
            'user_id' => $userWithoutRole->id,
            'role_id' => 8888, // Role ID yang tidak ada
        ]);
        $response->assertStatus(404)
                 ->assertJson([
                     'message' => 'Role not found for this user'
                 ]);
    }
}
