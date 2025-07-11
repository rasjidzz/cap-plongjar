<?php

namespace Tests\Feature\KetuaKK; // Perhatikan namespace ini

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\User_Role;
use App\Models\Dosen; // Import model Dosen
use App\Models\KelompokKeahlian; // Dibutuhkan oleh DosenFactory
use App\Models\JabatanStruktural; // Dibutuhkan oleh DosenFactory
use App\Models\ProgramStudi; // Dibutuhkan oleh DosenFactory dan helper
use App\Models\Pic; // Dibutuhkan oleh MatakuliahFactory jika ada

class ViewDosenTest extends TestCase
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

        // Buat role dasar yang dibutuhkan untuk otorisasi
        Role::firstOrCreate(['id' => 1, 'name' => 'Superadmin']);
        Role::firstOrCreate(['id' => 2, 'name' => 'ProgramStudi']);
        Role::firstOrCreate(['id' => 3, 'name' => 'KelompokKeahlian']); // Role Ketua KK
        Role::firstOrCreate(['id' => 4, 'name' => 'LayananAkademik']); // Contoh role tidak terotorisasi

        // Buat data dasar untuk Foreign Key Factories
        for ($i = 1; $i <= 10; $i++) {
            KelompokKeahlian::firstOrCreate(['id' => $i, 'nama' => 'KK ' . chr(64 + $i)]);
            JabatanStruktural::firstOrCreate(['id' => $i, 'nama' => 'Jabatan ' . $i, 'konversi_sks' => $i]);
        }
        Pic::firstOrCreate(['id' => 1, 'name' => 'Default PIC']); // Untuk MatakuliahFactory
        ProgramStudi::firstOrCreate(['id' => 1, 'nama' => 'S1 Informatika']); // Untuk DosenFactory jika ada
    }

    /**
     * Helper method untuk membuat user Ketua KK.
     *
     * @param \App\Models\KelompokKeahlian $kelompokKeahlian
     * @return \App\Models\User
     */
    protected function createKetuaKKUser(KelompokKeahlian $kelompokKeahlian)
    {
        $user = User::factory()->create();
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
     * Helper method untuk membuat user yang tidak terotorisasi.
     *
     * @return \App\Models\User
     */
    protected function createUnauthorizedUser()
    {
        $user = User::factory()->create();
        $unauthRole = Role::where('name', 'LayananAkademik')->first();
        User_Role::create([
            'user_id' => $user->id,
            'role_id' => $unauthRole->id,
            'roleable_type' => null,
            'roleable_id' => null,
        ]);
        return $user;
    }

    /**
     * Test Case: Ketua KK dapat melihat daftar semua dosen dengan paginasi default.
     * HTTP Response status code = 200 (Success)
     *
     * @return void
     */
    public function test_ketua_kk_can_view_all_dosens_paginated_successfully(): void
    {
        // 1. Persiapan Data
        $kelompokKeahlian = KelompokKeahlian::first();
        $ketuaKKUser = $this->createKetuaKKUser($kelompokKeahlian);
        Dosen::factory()->count(15)->create(); // Buat 15 dosen untuk menguji paginasi default (10 per page)

        // 2. Aksi: Login sebagai Ketua KK dan kirim GET request tanpa parameter pencarian
        // Endpoint: /api/v1/masterdata/getAllDosen
        $response = $this->actingAs($ketuaKKUser, 'sanctum')->getJson('/api/v1/masterdata/getAllDosen');

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

     public function test_ketua_kk_can_view_all_dosens_without_pagination_or_search_index_method(): void
    {
        // 1. Persiapan Data
        $kelompokKeahlian = KelompokKeahlian::first();
        $ketuaKKUser = $this->createKetuaKKUser($kelompokKeahlian);
        Dosen::factory()->count(5)->create(); // Buat beberapa dosen

        // 2. Aksi: Login sebagai Ketua KK dan kirim GET request ke endpoint index
        // Endpoint: /api/v1/masterdata/dosens (dari Route::apiResource('dosens', DosenController::class);)
        $response = $this->actingAs($ketuaKKUser, 'sanctum')->getJson('/api/v1/masterdata/dosens');

        // 3. Assertions
        $response->assertStatus(200) // Memastikan status HTTP 200 (Success)
                 ->assertJson([
                     'success' => true,
                     'message' => 'List All Dosen Data', // Pesan dari DosenController@index
                 ]);

        // Memverifikasi struktur data (tidak ada paginasi, hanya array data)
        $response->assertJsonStructure([
            'success',
            'message',
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
            ]
        ]);

        // Memastikan jumlah data sesuai dengan yang dibuat
        $this->assertCount(5, $response->json('data'));
    }

     public function test_ketua_kk_can_view_dosen_detail_data_successfully(): void
    {
         // 1. Persiapan Data
        $kelompokKeahlian = KelompokKeahlian::first();
        $ketuaKKUser = $this->createKetuaKKUser($kelompokKeahlian);
        $jabatanStruktural = JabatanStruktural::first(); // Pastikan ada jabatan struktural
        $dosen = Dosen::factory()->create([
            'id_kelompok_keahlian' => $kelompokKeahlian->id,
            'id_jabatan_struktural' => $jabatanStruktural->id,
            'name' => 'Prof. Budi Santoso',
            'lecturer_code' => 'BSS',
            'nip' => '12345678901234',
            'nidn' => '9876543210',
            'email' => 'budi.santoso@example.com',
            'jabatan_fungsional_akademik' => 'guru besar',
            'status_pegawai' => 'Pegawai Tetap',
            'pendidikan_terakhir' => 'S-3',
        ]);

        // 2. Aksi: Login sebagai Kaprodi dan kirim GET request untuk detail dosen
        // Endpoint: /api/v1/masterdata/getDosenDetail/{id_dosen}
        $response = $this->actingAs($ketuaKKUser, 'sanctum')->getJson('/api/v1/masterdata/getDosenDetail/' . $dosen->id);

        // 3. Assertions
        $response->assertStatus(200) // Memastikan status HTTP 200 (Success)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Detail Data Dosen',
                     'data' => [
                     ]
                 ]);

        // Memverifikasi struktur respons
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'nama_dosen',
                'kode_dosen',
                'jabatan',
                'home_base',
                'nip',
                'nidn',
                'bidang_keahlian',
                'status',
                'contact_person',
                'jfa',
                'riwayat_pengajaran', // URL ini akan dinamis
                'pendidikan',
            ]
        ]);
    }

     public function test_ketua_kk_cannot_view_dosen_detail_if_dosen_not_found(): void
    {
        // 1. Persiapan Data
        $kelompokKeahlian = KelompokKeahlian::first();
        $ketuaKKUser = $this->createKetuaKKUser($kelompokKeahlian);
        $nonExistentDosenId = 9999; // ID Dosen yang pasti tidak ada

        // 2. Aksi: Login sebagai Kaprodi dan kirim GET request untuk detail dosen dengan ID tidak ada
        $response = $this->actingAs($ketuaKKUser, 'sanctum')->getJson('/api/v1/masterdata/getDosenDetail/' . $nonExistentDosenId);

        // 3. Assertions
        $response->assertStatus(404) // Memastikan status HTTP 404
                 ->assertJson([
                     'success' => false,
                     'message' => 'Dosen tidak ditemukan.',
                     'data' => null,
                 ]);
    }
}
