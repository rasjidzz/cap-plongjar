<?php

namespace Tests\Feature\KaurLab; // Namespace baru untuk Kepala Urusan Laboratorium

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
    use RefreshDatabase; // Memastikan database bersih untuk setiap test
    use WithFaker;     // Untuk membuat data dummy

    /**
     * Setup the test environment.
     * Membuat role yang dibutuhkan sebelum setiap test dijalankan.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Buat role dasar yang dibutuhkan
        Role::firstOrCreate(['id' => 1, 'name' => 'Superadmin']);
        Role::firstOrCreate(['id' => 2, 'name' => 'ProgramStudi']);
        Role::firstOrCreate(['id' => 3, 'name' => 'KelompokKeahlian']);
        Role::firstOrCreate(['id' => 4, 'name' => 'LayananAkademik']);
        Role::firstOrCreate(['id' => 5, 'name' => 'KepalaUrusanLab']); // Role Kepala Urusan Lab
    }

    /**
     * Helper method untuk membuat user Kepala Urusan Laboratorium (Kaur Lab).
     *
     * @return \App\Models\User
     */
    protected function createKaurLabUser()
    {
        $user = User::factory()->create([
            'email' => $this->faker->unique()->safeEmail,
            'password' => Hash::make('password'), // Asumsi password default adalah 'password'
            'name' => 'User Kepala Urusan Lab',
        ]);
        $kaurLabRole = Role::where('name', 'KepalaUrusanLab')->first();
        User_Role::create([
            'user_id' => $user->id,
            'role_id' => $kaurLabRole->id,
            'roleable_type' => null, // Kaur Lab tidak terhubung ke entitas roleable spesifik
            'roleable_id' => null,
        ]);
        return $user;
    }

    /**
     * Test Case: Login Kaur Lab Berhasil
     * Memastikan user dengan role 'KepalaUrusanLab' dapat login.
     *
     * @return void
     */
    public function test_kaur_lab_can_login_successfully(): void
    {
        // 1. Persiapan Data
        $kaurLabUser = $this->createKaurLabUser(); // Buat user Kaur Lab

        $password = 'password'; // Sesuaikan dengan password di createKaurLabUser

        // 2. Aksi: Kirim POST request ke endpoint login
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $kaurLabUser->email,
            'password' => $password,
        ]);

        // 3. Assertions: Verifikasi respons
        $response->assertStatus(200) // Memastikan status HTTP 200 (Success)
                 ->assertJson([
                     'status' => 'success',
                     'message' => 'Login success',
                     'user' => [
                         'id' => $kaurLabUser->id,
                         'name' => $kaurLabUser->name,
                         'email' => $kaurLabUser->email,
                     ],
                     'roles' => [
                         [
                             'role_id' => Role::where('name', 'KepalaUrusanLab')->first()->id,
                             'role_name' => 'KepalaUrusanLab',
                             'roleable_type' => null,
                             'roleable_id' => null,
                             'roleable_name' => null, // Kaur Lab tidak punya roleable_name
                         ]
                     ]
                 ]);

        $response->assertJsonStructure([
            'status',
            'message',
            'token',
            'user' => [
                'id',
                'name',
                'email',
            ],
            'roles' => [
                '*' => [
                    'role_id',
                    'role_name',
                    'roleable_type',
                    'roleable_id',
                    'roleable_name',
                ]
            ]
        ]);

        // Verifikasi token tersimpan di database
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $kaurLabUser->id,
            'name' => $kaurLabUser->email,
        ]);
    }

    /**
     * Test Case: Login Kaur Lab Gagal (Password Salah)
     * Memastikan user Kaur Lab tidak dapat login dengan password yang salah.
     * HTTP Response status code = 401 (Login Failed)
     *
     * @return void
     */
    public function test_kaur_lab_cannot_login_with_incorrect_password(): void
    {
        // 1. Persiapan Data
        $kaurLabUser = $this->createKaurLabUser();

        // 2. Aksi: Kirim POST request dengan password yang salah
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $kaurLabUser->email,
            'password' => 'wrong-password', // Password salah
        ]);

        // 3. Assertions: Verifikasi respons
        $response->assertStatus(401)
                 ->assertJson([
                     'status' => 'Login Failed',
                     'message' => 'The provided credentials are incorrect'
                 ]);

        // Pastikan tidak ada token yang dibuat untuk user ini
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $kaurLabUser->id,
        ]);
    }

    /**
     * Test Case: Login Kaur Lab Gagal (Email Tidak Terdaftar)
     * Memastikan user Kaur Lab tidak dapat login jika email tidak terdaftar.
     * HTTP Response status code = 401 (Login Failed)
     *
     * @return void
     */
    public function test_kaur_lab_cannot_login_with_non_existent_email(): void
    {
        // 1. Aksi: Kirim POST request dengan email yang tidak terdaftar
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'nonexistent_kaurlab@example.com', // Email tidak terdaftar
            'password' => 'anypassword',
        ]);

        // 2. Assertions: Verifikasi respons
        $response->assertStatus(401)
                 ->assertJson([
                     'status' => 'Login Failed',
                     'message' => 'The provided credentials are incorrect'
                 ]);
    }

    /**
     * Test Case: Logout Kaur Lab Berhasil
     * Memastikan Kaur Lab dapat logout dari sistem.
     * HTTP Response status code = 200 (Success)
     *
     * @return void
     */
    public function test_kaur_lab_can_logout_successfully(): void
    {
        // 1. Persiapan Data
        $kaurLabUser = $this->createKaurLabUser();

        // Lakukan login melalui API untuk mendapatkan token sungguhan
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $kaurLabUser->email,
            'password' => 'password',
        ]);

        $loginResponse->assertStatus(200);
        $token = $loginResponse->json('token.plainTextToken');

        $tokenParts = explode('|', $token);
        $tokenId = $tokenParts[0];

        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $tokenId,
            'tokenable_id' => $kaurLabUser->id,
        ]);

        // 2. Aksi: Kirim POST request ke endpoint logout
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
            'tokenable_id' => $kaurLabUser->id,
        ]);
    }
}
