<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\User_Role;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

class AuthTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Pastikan roles dasar ada di database test
        Role::firstOrCreate(['id' => 1, 'name' => 'Superadmin']);
        Role::firstOrCreate(['id' => 2, 'name' => 'ProgramStudi']);
        Role::firstOrCreate(['id' => 3, 'name' => 'KelompokKeahlian']);
    }

    /**
     * Helper method untuk membuat user admin yang bisa melakukan aksi.
     *
     * @return \App\Models\User
     */
    protected function createAdminUser()
    {
        $admin = User::factory()->create([
            'email' => 'admin_test@example.com', // Gunakan email unik untuk admin test
            'password' => Hash::make('password'), // Pastikan password default untuk factory adalah 'password'
        ]);
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
     * Test Case: FR-ADM-01LoginTC-ADM-01
     * Skenario: Login Admin Berhasil
     * Memastikan Admin dapat login dengan memasukkan credential yang benar.
     * HTTP Response status code = 200 (Success).
     *
     * @return void
     */
    public function test_admin_login_success_with_correct_credentials(): void
    {
        // 1. Persiapan Data
        $password = 'password123';
        $user = User::factory()->create([
            'email' => 'user_login@example.com', // Email unik untuk test ini
            'password' => Hash::make($password),
            'name' => 'Test User Login',
        ]);

        // Asumsikan user ini memiliki role Superadmin
        $superadminRole = Role::where('name', 'Superadmin')->first();
        User_Role::create([
            'user_id' => $user->id,
            'role_id' => $superadminRole->id,
            'roleable_type' => null,
            'roleable_id' => null,
        ]);

        // 2. Aksi: Mengirim POST request ke endpoint login
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => $password,
        ]);

        // 3. Assertions: Memverifikasi respons dan database
        $response->assertStatus(200)
                 ->assertJson([
                     'status' => 'success',
                     'message' => 'Login success',
                     'user' => [
                         'id' => $user->id,
                         'name' => $user->name,
                         'email' => $user->email,
                     ],
                     'roles' => [
                         [
                             'role_id' => $superadminRole->id,
                             'role_name' => 'Superadmin',
                             'roleable_type' => null,
                             'roleable_id' => null,
                             'roleable_name' => null,
                         ]
                     ]
                 ]);

        $response->assertJsonStructure([
            'status',
            'message',
            'token' => [
                'accessToken',
                'plainTextToken',
            ],
            'user' => [
                'id',
                'name',
                'email',
            ],
            'roles'
        ]);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => $user->email,
        ]);
    }

    /**
     * Test Case: FR-ADM-01LoginTC-ADM-02
     * Skenario: Login Admin Gagal (Password Salah)
     * Memastikan Admin tidak dapat login dengan memasukkan credential yang salah.
     * HTTP Response status code = 401 (Login Failed).
     *
     * @return void
     */
    public function test_admin_login_failure_with_incorrect_password(): void
    {
        // 1. Persiapan Data
        $password = 'password123';
        $user = User::factory()->create([
            'email' => 'wrongpass@example.com', // Email unik untuk test ini
            'password' => Hash::make($password),
        ]);

        // 2. Aksi: Mengirim POST request dengan password yang salah
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        // 3. Assertions: Memverifikasi respons
        $response->assertStatus(401)
                 ->assertJson([
                     'status' => 'Login Failed',
                     'message' => 'The provided credentials are incorrect',
                 ]);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    }

    /**
     * Test Case: FR-ADM-01LoginTC-ADM-02 (Skenario Tambahan)
     * Skenario: Login Admin Gagal (Email Tidak Terdaftar)
     * Memastikan Admin tidak dapat login jika email tidak terdaftar.
     * HTTP Response status code = 401 (Login Failed).
     *
     * @return void
     */
    public function test_admin_login_failure_with_non_existent_email(): void
    {
        // 1. Aksi: Mengirim POST request dengan email yang tidak terdaftar
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'anypassword',
        ]);

        // 2. Assertions: Memverifikasi respons
        $response->assertStatus(401)
                 ->assertJson([
                     'status' => 'Login Failed',
                     'message' => 'The provided credentials are incorrect',
                 ]);
    }

    /**
     * TC-ADM-21: Logout Admin Berhasil.
     * Memastikan Admin dapat logout dari sistem.
     * HTTP Response status code = 200 (Success)
     *
     * @return void
     */
    public function test_admin_can_logout_successfully(): void
    {
        // 1. Persiapan Data
        $admin = $this->createAdminUser(); // Membuat user admin dengan password 'password'

        // Simulate login to get a token. This token will be used for logout.
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $admin->email,
            'password' => 'password', // Gunakan password yang sama dengan yang dibuat di createAdminUser
        ]);

        // Pastikan login berhasil dan dapatkan plainTextToken
        $loginResponse->assertStatus(200);
        $token = $loginResponse->json('token.plainTextToken');

        // Extract the token ID from the plainTextToken (e.g., "1|randomstring")
        $tokenParts = explode('|', $token);
        $tokenId = $tokenParts[0];

        // Assert that the token is present in the database before logout
        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $tokenId,
            'tokenable_id' => $admin->id,
        ]);

        // 2. Aksi: Kirim POST request ke endpoint logout, menggunakan token yang didapat dari login
        // Penting: Jangan gunakan actingAs() lagi di sini jika Anda ingin memastikan token spesifik ini dihapus.
        // Cukup kirim header Authorization.
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/auth/logout');

        // 3. Assertions
        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Logged out successfully',
                 ]);

        // Verifikasi database: memastikan token spesifik telah dihapus
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $tokenId,
            'tokenable_id' => $admin->id,
        ]);
    }
}
