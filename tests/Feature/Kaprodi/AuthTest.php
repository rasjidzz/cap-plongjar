<?php

namespace Tests\Feature\Kaprodi; // Perhatikan namespace ini

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\User_Role;
use App\Models\ProgramStudi; // Import ProgramStudi model
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken; // Import PersonalAccessToken

class AuthTest extends TestCase
{
    use RefreshDatabase; // Memastikan database bersih untuk setiap test
    use WithFaker;     // Untuk membuat data dummy

    /**
     * Setup the test environment.
     * Membuat user dan role yang dibutuhkan sebelum setiap test dijalankan.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Buat role dasar yang dibutuhkan
        // Role ID 2 diasumsikan adalah ProgramStudi
        Role::firstOrCreate(['id' => 1, 'name' => 'Superadmin']);
        Role::firstOrCreate(['id' => 2, 'name' => 'ProgramStudi']);
        Role::firstOrCreate(['id' => 3, 'name' => 'KelompokKeahlian']); // Jika diperlukan oleh factory lain
    }

    /**
     * Helper method untuk membuat user ProgramStudi.
     *
     * @param \App\Models\ProgramStudi $programStudi
     * @return \App\Models\User
     */
    protected function createProgramStudiUser(ProgramStudi $programStudi)
    {
        $user = User::factory()->create([
            'email' => $this->faker->unique()->safeEmail,
            'password' => Hash::make('password'), // Asumsi password default adalah 'password'
            'name' => 'User Kaprodi ' . $programStudi->nama,
        ]);
        $prodiRole = Role::where('name', 'ProgramStudi')->first();
        User_Role::create([
            'user_id' => $user->id,
            'role_id' => $prodiRole->id,
            'roleable_type' => ProgramStudi::class,
            'roleable_id' => $programStudi->id,
        ]);
        return $user;
    }

    /**
     * Test Case: Login Kaprodi Berhasil
     * Memastikan user dengan role 'ProgramStudi' dapat login.
     * Respons harus mencerminkan roleable_type dan roleable_name yang benar.
     *
     * @return void
     */
    public function test_kaprodi_can_login_successfully(): void
    {
        // 1. Persiapan Data
        $programStudi = ProgramStudi::factory()->create(['nama' => 'S1 Teknik Informatika']); // Buat Program Studi dummy
        $kaprodiUser = $this->createProgramStudiUser($programStudi); // Buat user Kaprodi untuk PS ini

        $password = 'password'; // Sesuaikan dengan password di createProgramStudiUser

        // 2. Aksi: Kirim POST request ke endpoint login
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $kaprodiUser->email,
            'password' => $password,
        ]);

        // 3. Assertions: Verifikasi respons
        $response->assertStatus(200) // Memastikan status HTTP 200 (Success)
                 ->assertJson([
                     'status' => 'success',
                     'message' => 'Login success',
                     'user' => [
                         'id' => $kaprodiUser->id,
                         'name' => $kaprodiUser->name,
                         'email' => $kaprodiUser->email,
                     ],
                     'roles' => [
                         [
                             'role_id' => Role::where('name', 'ProgramStudi')->first()->id,
                             'role_name' => 'ProgramStudi',
                             'roleable_type' => ProgramStudi::class,
                             'roleable_id' => $programStudi->id,
                             'roleable_name' => $programStudi->nama, // Memastikan nama roleable ada
                         ]
                     ]
                 ]);

    }

    public function test_kaprodi_cannot_login_with_incorrect_password(): void
    {
        // 1. Persiapan Data
        $programStudi = ProgramStudi::factory()->create(['nama' => 'S1 Sistem Informasi']);
        $kaprodiUser = $this->createProgramStudiUser($programStudi);
        $correctPassword = 'password';

        // 2. Aksi: Kirim POST request dengan password yang salah
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $kaprodiUser->email,
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
            'tokenable_id' => $kaprodiUser->id,
        ]);
    }

    /**
     * Test Case: Login Kaprodi Gagal (Email Tidak Terdaftar)
     * Memastikan user Kaprodi tidak dapat login jika email tidak terdaftar.
     * HTTP Response status code = 401 (Login Failed)
     *
     * @return void
     */
    public function test_kaprodi_cannot_login_with_non_existent_email(): void
    {
        // 1. Aksi: Kirim POST request dengan email yang tidak terdaftar
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'nonexistent_kaprodi@example.com', // Email tidak terdaftar
            'password' => 'anypassword',
        ]);

        // 2. Assertions: Verifikasi respons
        $response->assertStatus(401) // Memastikan status HTTP 401 (Unauthorized)
                 ->assertJson([
                     'status' => 'Login Failed',
                     'message' => 'The provided credentials are incorrect'
                 ]);
    }

     public function test_kaprodi_can_logout_successfully(): void
    {
        // 1. Persiapan Data
        $programStudi = ProgramStudi::factory()->create(['nama' => 'S1 Teknik Elektro']);
        $kaprodiUser = $this->createProgramStudiUser($programStudi);

        // Lakukan login melalui API untuk mendapatkan token sungguhan
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $kaprodiUser->email,
            'password' => 'password', // Gunakan password yang sama dengan yang dibuat di createProgramStudiUser
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
            'tokenable_id' => $kaprodiUser->id,
        ]);

        // 2. Aksi: Kirim POST request ke endpoint logout
        // Kirim token melalui header Authorization.
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
            'tokenable_id' => $kaprodiUser->id,
        ]);
    }
}
