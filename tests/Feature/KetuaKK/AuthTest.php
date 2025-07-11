<?php

namespace Tests\Feature\KetuaKK; // Namespace diubah untuk path baru

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\User_Role;
use App\Models\KelompokKeahlian; // Import KelompokKeahlian model
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
        Role::firstOrCreate(['id' => 3, 'name' => 'KelompokKeahlian']); // Role Ketua KK
    }

    /**
     * Helper method untuk membuat user Ketua KK.
     *
     * @param \App\Models\KelompokKeahlian $kelompokKeahlian
     * @return \App\Models\User
     */
    protected function createKetuaKKUser(KelompokKeahlian $kelompokKeahlian)
    {
        $user = User::factory()->create([
            'email' => $this->faker->unique()->safeEmail,
            'password' => Hash::make('password'), // Asumsi password default adalah 'password'
            'name' => 'User Ketua KK ' . $kelompokKeahlian->nama,
        ]);
        $ketuaKKRole = Role::where('name', 'KelompokKeahlian')->first();
        User_Role::create([
            'user_id' => $user->id,
            'role_id' => $ketuaKKRole->id,
            'roleable_type' => KelompokKeahlian::class,
            'roleable_id' => $kelompokKeahlian->id,
        ]);
        return $user;
    }

    /**
     * Test Case: Login Ketua KK Berhasil
     * Memastikan user dengan role 'KelompokKeahlian' dapat login.
     * Respons harus mencerminkan roleable_type dan roleable_name yang benar.
     *
     * @return void
     */
    public function test_ketua_kk_can_login_successfully(): void
    {
        // 1. Persiapan Data
        $kelompokKeahlian = KelompokKeahlian::factory()->create(['nama' => 'SEAL']); // Buat Kelompok Keahlian dummy
        $ketuaKKUser = $this->createKetuaKKUser($kelompokKeahlian); // Buat user Ketua KK

        $password = 'password'; // Sesuaikan dengan password di createKetuaKKUser

        // 2. Aksi: Kirim POST request ke endpoint login
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $ketuaKKUser->email,
            'password' => $password,
        ]);

        // 3. Assertions: Verifikasi respons
        $response->assertStatus(200) // Memastikan status HTTP 200 (Success)
                 ->assertJson([
                     'status' => 'success',
                     'message' => 'Login success',
                     'user' => [
                         'id' => $ketuaKKUser->id,
                         'name' => $ketuaKKUser->name,
                         'email' => $ketuaKKUser->email,
                     ],
                     'roles' => [
                         [
                             'role_id' => Role::where('name', 'KelompokKeahlian')->first()->id,
                             'role_name' => 'KelompokKeahlian',
                             'roleable_type' => KelompokKeahlian::class,
                             'roleable_id' => $kelompokKeahlian->id,
                             'roleable_name' => $kelompokKeahlian->nama, // Memastikan nama roleable ada
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
            'tokenable_id' => $ketuaKKUser->id,
            'name' => $ketuaKKUser->email, // Asumsi nama token adalah email user
        ]);
    }

    /**
     * Test Case: Login Ketua KK Gagal (Password Salah)
     * Memastikan user Ketua KK tidak dapat login dengan password yang salah.
     * HTTP Response status code = 401 (Login Failed)
     *
     * @return void
     */
    public function test_ketua_kk_cannot_login_with_incorrect_password(): void
    {
        // 1. Persiapan Data
        $kelompokKeahlian = KelompokKeahlian::factory()->create(['nama' => 'CITI']);
        $ketuaKKUser = $this->createKetuaKKUser($kelompokKeahlian);
        $correctPassword = 'password';

        // 2. Aksi: Kirim POST request dengan password yang salah
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $ketuaKKUser->email,
            'password' => 'wrong-password', // Password salah
        ]);

        // 3. Assertions: Verifikasi respons
        $response->assertStatus(401) // Memastikan status HTTP 401 (Unauthorized)
                 ->assertJson([
                     'status' => 'Login Failed',
                     'message' => 'The provided credentials are incorrect'
                 ]);

        // Pastikan tidak ada token yang dibuat untuk user ini
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $ketuaKKUser->id,
        ]);
    }

    /**
     * Test Case: Login Ketua KK Gagal (Email Tidak Terdaftar)
     * Memastikan user Ketua KK tidak dapat login jika email tidak terdaftar.
     * HTTP Response status code = 401 (Login Failed)
     *
     * @return void
     */
    // public function test_ketua_kk_cannot_login_with_non_existent_email(): void
    // {
    //     // 1. Aksi: Kirim POST request dengan email yang tidak terdaftar
    //     $response = $this->postJson('/api/v1/auth/login', [
    //         'email' => 'nonexistent_ketuakk@example.com', // Email tidak terdaftar
    //         'password' => 'anypasswordpas',
    //     ]);

    //     // 2. Assertions: Verifikasi respons
    //     $response->assertStatus(401)
    //              ->assertJson([
    //                  'status' => 'Login Failed',
    //                  'message' => 'The provided credentials are incorrect'
    //              ]);
    // }

    /**
     * Test Case: Logout Ketua KK Berhasil
     * Memastikan Ketua KK dapat logout dari sistem.
     * HTTP Response status code = 200 (Success)
     *
     * @return void
     */
    public function test_ketua_kk_can_logout_successfully(): void
    {
        // 1. Persiapan Data
        $kelompokKeahlian = KelompokKeahlian::factory()->create(['nama' => 'DSIS']);
        $ketuaKKUser = $this->createKetuaKKUser($kelompokKeahlian);

        // Lakukan login melalui API untuk mendapatkan token sungguhan
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $ketuaKKUser->email,
            'password' => 'password',
        ]);

        $loginResponse->assertStatus(200);
        $token = $loginResponse->json('token.plainTextToken');

        $tokenParts = explode('|', $token);
        $tokenId = $tokenParts[0];

        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $tokenId,
            'tokenable_id' => $ketuaKKUser->id,
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

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $tokenId,
            'tokenable_id' => $ketuaKKUser->id,
        ]);
    }
}
