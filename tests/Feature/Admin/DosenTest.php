<?php

namespace Tests\Feature\Admin; // Perhatikan namespace ini

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\User_Role;
use App\Models\Dosen; // Import model Dosen
use App\Models\KelompokKeahlian; // Dibutuhkan oleh DosenFactory
use App\Models\JabatanStruktural; // Dibutuhkan oleh DosenFactory
use App\Models\ProgramStudi; // Dibutuhkan oleh DosenFactory jika ada
use App\Models\Pic; // Dibutuhkan oleh MatakuliahFactory jika ada

class DosenTest extends TestCase
{
    use RefreshDatabase; // Memastikan database bersih untuk setiap test
    use WithFaker;     // Untuk membuat data dummy

    /**
     * Setup the test environment.
     * Membuat data dasar yang dibutuhkan sebelum setiap test dijalankan.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Buat role 'Superadmin' jika belum ada
        Role::firstOrCreate(['id' => 1, 'name' => 'Superadmin']);
        // Buat role lain yang mungkin dibutuhkan oleh factories atau middleware
        Role::firstOrCreate(['id' => 2, 'name' => 'ProgramStudi']);
        Role::firstOrCreate(['id' => 3, 'name' => 'KelompokKeahlian']);
        Role::firstOrCreate(['id' => 4, 'name' => 'LayananAkademik']);

        // Buat data dasar untuk Foreign Key Factories
        for ($i = 1; $i <= 10; $i++) {
            KelompokKeahlian::firstOrCreate(['id' => $i, 'nama' => 'KK ' . chr(64 + $i)]);
            JabatanStruktural::firstOrCreate(['id' => $i, 'nama' => 'Jabatan ' . $i, 'konversi_sks' => $i]);
        }
        Pic::firstOrCreate(['id' => 1, 'name' => 'Default PIC']);
        ProgramStudi::firstOrCreate(['id' => 1, 'nama' => 'S1 Informatika']);
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
     * Test Case: Admin berhasil melihat daftar semua dosen dengan paginasi default.
     * HTTP Response status code = 200 (Success)
     *
     * @return void
     */
    public function test_admin_can_view_all_dosens_paginated_successfully(): void
    {
        // 1. Persiapan Data
        $admin = $this->createAdminUser();
        Dosen::factory()->count(15)->create(); // Buat 15 dosen untuk menguji paginasi default (10 per page)

        // 2. Aksi: Login sebagai admin dan kirim GET request tanpa parameter pencarian
        // Endpoint: /api/v1/masterdata/getAllDosen
        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/masterdata/getAllDosen');

        // 3. Assertions
        $response->assertStatus(200) // Memastikan status HTTP 200 (Success)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Daftar semua dosen berhasil dimuat.',
                 ]);

        // Memverifikasi struktur paginasi
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'current_page',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'lecturer_code',
                        'nip',
                        'status_pegawai',
                        'id_kelompok_keahlian',
                        'kelompok_keahlian' => ['id', 'nama'], // Relasi kelompokKeahlian dimuat
                    ]
                ],
                'first_page_url', 'from', 'last_page', 'last_page_url',
                'links', 'next_page_url', 'path', 'per_page',
                'prev_page_url', 'to', 'total',
            ]
        ]);

        // Memastikan jumlah data di halaman pertama adalah 10 (default per_page)
        $response->assertJsonCount(10, 'data.data');
        $this->assertEquals(15, $response->json('data.total')); // Memastikan total data adalah 15
    }

}
