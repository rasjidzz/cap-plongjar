<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\User_Role;
use Illuminate\Support\Facades\Hash;

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

        // Buat role 'Superadmin' jika belum ada.
        // Role ID 1 diasumsikan adalah Superadmin berdasarkan api.php
        Role::firstOrCreate(['id' => 1, 'name' => 'Superadmin']);
        Role::firstOrCreate(['id' => 2, 'name' => 'ProgramStudi']); // Untuk skenario roleable
        Role::firstOrCreate(['id' => 3, 'name' => 'KelompokKeahlian']); // Untuk skenario roleable
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
            'email' => 'admin@example.com',
            'password' => Hash::make($password),
            'name' => 'Admin Test',
        ]);

        // Asumsikan user ini memiliki role Superadmin
        $superadminRole = Role::where('name', 'Superadmin')->first(); //
        User_Role::create([ //
            'user_id' => $user->id, //
            'role_id' => $superadminRole->id, //
            'roleable_type' => null, //
            'roleable_id' => null, //
        ]);

        // 2. Aksi: Mengirim POST request ke endpoint login
        $response = $this->postJson('/api/v1/auth/login', [ //
            'email' => $user->email, //
            'password' => $password, //
        ]);

        // 3. Assertions: Memverifikasi respons dan database
        $response->assertStatus(200); // Memastikan status HTTP 200 (Success)

        $response->assertJson([ // Memastikan isi JSON response sesuai
            'status' => 'success', //
            'message' => 'Login success', //
            'user' => [ //
                'id' => $user->id, //
                'name' => $user->name, //
                'email' => $user->email, //
            ],
            'roles' => [ //
                [
                    'role_id' => $superadminRole->id, //
                    'role_name' => 'Superadmin', //
                    'roleable_type' => null, //
                    'roleable_id' => null, //
                    'roleable_name' => null, //
                ]
            ]
        ]);

        // Memastikan struktur token dan user ada
        $response->assertJsonStructure([ //
            'status', //
            'message', //
            'token' => [ //
                'accessToken', //
                'plainTextToken', //
            ],
            'user' => [ //
                'id', //
                'name', //
                'email', //
            ],
            'roles' //
        ]);

        // Opsional: Memastikan token tersimpan di database
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id, //
            'name' => $user->email, // Asumsi nama token adalah email user
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
            'email' => 'admin@example.com',
            'password' => Hash::make($password),
        ]);

        // 2. Aksi: Mengirim POST request dengan password yang salah
        $response = $this->postJson('/api/v1/auth/login', [ //
            'email' => $user->email, //
            'password' => 'wrong-password', // Password salah
        ]);

        // 3. Assertions: Memverifikasi respons
        $response->assertStatus(401); // Memastikan status HTTP 401 (Unauthorized)
        $response->assertJson([ // Memastikan isi JSON response sesuai
            'status' => 'Login Failed', //
            'message' => 'The provided credentials are incorrect', //
        ]);

        // Opsional: Memastikan tidak ada token yang dibuat di database
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id, //
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
        $response = $this->postJson('/api/v1/auth/login', [ //
            'email' => 'nonexistent@example.com', // Email tidak terdaftar
            'password' => 'anypassword',
        ]);

        // 2. Assertions: Memverifikasi respons
        $response->assertStatus(401); // Memastikan status HTTP 401 (Unauthorized)
        $response->assertJson([ // Memastikan isi JSON response sesuai
            'status' => 'Login Failed', //
            'message' => 'The provided credentials are incorrect', //
        ]);
    }
}
