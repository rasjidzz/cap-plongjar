<?php

namespace Tests\Feature\LAA; // Name
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
        Role::firstOrCreate(['id' => 4, 'name' => 'LayananAkademik']); // Role Layanan Akademik
    }

    /**
     * Helper method untuk membuat user Layanan Akademik (LAA).
     *
     * @return \App\Models\User
     */
    protected function createLAAUser()
    {
        $user = User::factory()->create([
            'email' => $this->faker->unique()->safeEmail,
            'password' => Hash::make('password'), // Asumsi password default adalah 'password'
            'name' => 'User Layanan Akademik',
        ]);
        $laaRole = Role::where('name', 'LayananAkademik')->first();
        User_Role::create([
            'user_id' => $user->id,
            'role_id' => $laaRole->id,
            'roleable_type' => null, // LAA tidak terhubung ke entitas roleable spesifik
            'roleable_id' => null,
        ]);
        return $user;
    }

    /**
     * Test Case: Login LAA Berhasil
     * Memastikan user dengan role 'LayananAkademik' dapat login.
     *
     * @return void
     */
    public function test_laa_can_login_successfully(): void
    {
        // 1. Persiapan Data
        $laaUser = $this->createLAAUser(); // Buat user LAA

        $password = 'password'; // Sesuaikan dengan password di createLAAUser

        // 2. Aksi: Kirim POST request ke endpoint login
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $laaUser->email,
            'password' => $password,
        ]);

        // 3. Assertions: Verifikasi respons
        $response->assertStatus(200) // Memastikan status HTTP 200 (Success)
                 ->assertJson([
                     'status' => 'success',
                     'message' => 'Login success',
                     'user' => [
                         'id' => $laaUser->id,
                         'name' => $laaUser->name,
                         'email' => $laaUser->email,
                     ],
                     'roles' => [
                         [
                             'role_id' => Role::where('name', 'LayananAkademik')->first()->id,
                             'role_name' => 'LayananAkademik',
                             'roleable_type' => null,
                             'roleable_id' => null,
                             'roleable_name' => null, // LAA tidak punya roleable_name
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
            'tokenable_id' => $laaUser->id,
            'name' => $laaUser->email,
        ]);
    }

    /**
     * Test Case: Login LAA Gagal (Password Salah)
     * Memastikan user LAA tidak dapat login dengan password yang salah.
     * HTTP Response status code = 401 (Login Failed)
     *
     * @return void
     */
    public function test_laa_cannot_login_with_incorrect_password(): void
    {
        // 1. Persiapan Data
        $laaUser = $this->createLAAUser();
        $correctPassword = 'password';

        // 2. Aksi: Kirim POST request dengan password yang salah
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $laaUser->email,
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
            'tokenable_id' => $laaUser->id,
        ]);
    }

    /**
     * Test Case: Login LAA Gagal (Email Tidak Terdaftar)
     * Memastikan user LAA tidak dapat login jika email tidak terdaftar.
     * HTTP Response status code = 401 (Login Failed)
     *
     * @return void
     */
    public function test_laa_cannot_login_with_non_existent_email(): void
    {
        // 1. Aksi: Kirim POST request dengan email yang tidak terdaftar
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'nonexistent_laa@example.com', // Email tidak terdaftar
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
     * Test Case: Logout LAA Berhasil
     * Memastikan LAA dapat logout dari sistem.
     * HTTP Response status code = 200 (Success)
     *
     * @return void
     */
    public function test_laa_can_logout_successfully(): void
    {
        // 1. Persiapan Data
        $laaUser = $this->createLAAUser();

        // Lakukan login melalui API untuk mendapatkan token sungguhan
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $laaUser->email,
            'password' => 'password',
        ]);

        $loginResponse->assertStatus(200);
        $token = $loginResponse->json('token.plainTextToken');

        $tokenParts = explode('|', $token);
        $tokenId = $tokenParts[0];

        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $tokenId,
            'tokenable_id' => $laaUser->id,
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
            'tokenable_id' => $laaUser->id,
        ]);
    }
}
