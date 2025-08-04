<?php

namespace Tests\Feature\Kaprodi; // Perhatikan namespace ini

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\User_Role;
use App\Models\Dosen;
use App\Models\ProgramStudi;
use App\Models\TahunAjaran;
use App\Models\Matakuliah;
use App\Models\MappingKelasMatakuliah;
use App\Models\PlottinganPengajaran;
use App\Models\KelompokKeahlian;
use App\Models\JabatanStruktural;
use App\Models\Pic;

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

        // Buat role dasar yang dibutuhkan untuk otorisasi
        Role::firstOrCreate(['id' => 1, 'name' => 'Superadmin']);
        Role::firstOrCreate(['id' => 2, 'name' => 'ProgramStudi']); // Role Kaprodi
        Role::firstOrCreate(['id' => 3, 'name' => 'KelompokKeahlian']);
        Role::firstOrCreate(['id' => 4, 'name' => 'LayananAkademik']); // Contoh role tidak terotorisasi

        // Buat data dasar untuk Foreign Key Factories
        for ($i = 1; $i <= 10; $i++) {
            KelompokKeahlian::firstOrCreate(['id' => $i, 'nama' => 'KK ' . chr(64 + $i)]);
            JabatanStruktural::firstOrCreate(['id' => $i, 'nama' => 'Jabatan ' . $i, 'konversi_sks' => $i]);
        }
        ProgramStudi::firstOrCreate(['id' => 1, 'nama' => 'S1 Informatika']); // Untuk createProgramStudiUser
    }

    /**
     * Helper method untuk membuat user ProgramStudi.
     *
     * @param \App\Models\ProgramStudi $programStudi
     * @return \App\Models\User
     */
    protected function createProgramStudiUser(ProgramStudi $programStudi)
    {
        $user = User::factory()->create();
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
     * Test Case: Kaprodi dapat melihat daftar semua dosen dengan paginasi default.
     * HTTP Response status code = 200 (Success)
     *
     * @return void
     */
    public function test_kaprodi_can_view_all_dosens_paginated_successfully(): void
    {
        // 1. Persiapan Data
        $programStudi = ProgramStudi::first();
        $kaprodiUser = $this->createProgramStudiUser($programStudi);
        Dosen::factory()->count(15)->create(); // Buat 15 dosen untuk menguji paginasi default (10 per page)

        // 2. Aksi: Login sebagai Kaprodi dan kirim GET request tanpa parameter pencarian
        // Endpoint: /api/v1/masterdata/getAllDosen
        $response = $this->actingAs($kaprodiUser, 'sanctum')->getJson('/api/v1/masterdata/getAllDosen');

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

    public function test_kaprodi_can_view_all_dosens_without_pagination_or_search(): void
    {
        // 1. Persiapan Data
        $programStudi = ProgramStudi::first();
        $kaprodiUser = $this->createProgramStudiUser($programStudi);
        Dosen::factory()->count(5)->create(); // Buat beberapa dosen

        // 2. Aksi: Login sebagai Kaprodi dan kirim GET request ke endpoint index
        // Endpoint: /api/v1/masterdata/dosens (dari Route::apiResource('dosens', DosenController::class);)
        $response = $this->actingAs($kaprodiUser, 'sanctum')->getJson('/api/v1/masterdata/dosens');

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

    public function test_kaprodi_can_view_dosen_detail_data_successfully(): void
    {
        // 1. Persiapan Data
        $programStudi = ProgramStudi::first();
        $kaprodiUser = $this->createProgramStudiUser($programStudi);
        $kelompokKeahlian = KelompokKeahlian::first();
        $jabatanStruktural = JabatanStruktural::first(); // Pastikan ada jabatan struktural
        $dosen = Dosen::factory()->create([
            'id_kelompok_keahlian' => $kelompokKeahlian->id,
            'id_jabatan_struktural' => $jabatanStruktural->id, // Dosen memiliki jabatan struktural
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
        $response = $this->actingAs($kaprodiUser, 'sanctum')->getJson('/api/v1/masterdata/getDosenDetail/' . $dosen->id);

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

    /**
     * Test Case: Kaprodi gagal melihat detail data dosen jika dosen tidak ditemukan.
     * HTTP Response status code = 404 (Not Found)
     *
     * @return void
     */
    public function test_kaprodi_cannot_view_dosen_detail_if_dosen_not_found(): void
    {
        // 1. Persiapan Data
        $programStudi = ProgramStudi::first();
        $kaprodiUser = $this->createProgramStudiUser($programStudi);
        $nonExistentDosenId = 9999; // ID Dosen yang pasti tidak ada

        // 2. Aksi: Login sebagai Kaprodi dan kirim GET request untuk detail dosen dengan ID tidak ada
        $response = $this->actingAs($kaprodiUser, 'sanctum')->getJson('/api/v1/masterdata/getDosenDetail/' . $nonExistentDosenId);

        // 3. Assertions
        $response->assertStatus(404) // Memastikan status HTTP 404
                 ->assertJson([
                     'success' => false,
                     'message' => 'Dosen tidak ditemukan.',
                     'data' => null,
                 ]);
    }


    public function test_kaprodi_can_view_all_dosens_by_kk_id_successfully(): void
    {
        // 1. Persiapan Data
        $programStudi = ProgramStudi::first();
        $kaprodiUser = $this->createProgramStudiUser($programStudi);
        $kelompokKeahlianTarget = KelompokKeahlian::factory()->create(['nama' => 'KK Target']);
        $kelompokKeahlianOther = KelompokKeahlian::factory()->create(['nama' => 'KK Other']);

        // Buat dosen untuk KK target
        $dosen1 = Dosen::factory()->create(['id_kelompok_keahlian' => $kelompokKeahlianTarget->id, 'name' => 'Dosen KK Target 1']);
        $dosen2 = Dosen::factory()->create(['id_kelompok_keahlian' => $kelompokKeahlianTarget->id, 'name' => 'Dosen KK Target 2']);
        // Buat dosen untuk KK lain
        $dosenOther = Dosen::factory()->create(['id_kelompok_keahlian' => $kelompokKeahlianOther->id, 'name' => 'Dosen KK Other']);

        // 2. Aksi: Login sebagai Kaprodi dan kirim GET request
        // Endpoint: /api/v1/masterdata/getAllDosen/{id_kk}
        $response = $this->actingAs($kaprodiUser, 'sanctum')->getJson('/api/v1/masterdata/getAllDosen/' . $kelompokKeahlianTarget->id);

        // 3. Assertions
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'List All Dosen Data (id, name, lecturer_code, nip, kelompok_keahlian, status_pegawai)',
                 ]);

        // Memastikan hanya dosen dari KK target yang ada di respons
        $response->assertJsonCount(2, 'data');
        // Perbaikan: Sesuaikan assertJsonFragment dengan struktur JSON aktual
        $response->assertJsonFragment([
            'name' => 'Dosen KK Target 1',
            'kelompok_keahlian' => [
                'id' => $kelompokKeahlianTarget->id,
                'nama' => 'KK Target',
                'roleable_type' => 'App\\Models\\KelompokKeahlian', // Tambahkan ini
                'unit_type' => 'kelompok_keahlian' // Tambahkan ini
            ]
        ]);
        $response->assertJsonFragment([
            'name' => 'Dosen KK Target 2',
            'kelompok_keahlian' => [
                'id' => $kelompokKeahlianTarget->id,
                'nama' => 'KK Target',
                'roleable_type' => 'App\\Models\\KelompokKeahlian', // Tambahkan ini
                'unit_type' => 'kelompok_keahlian' // Tambahkan ini
            ]
        ]);
        $response->assertJsonMissing(['name' => 'Dosen KK Other']);
    }

     public function test_kaprodi_can_view_dosen_teaching_history_successfully(): void
    {
        // 1. Persiapan Data
        $programStudi = ProgramStudi::first();
        $kaprodiUser = $this->createProgramStudiUser($programStudi);
        $dosen = Dosen::factory()->create(); // Dosen yang riwayatnya akan dilihat

        $tahunAjaran1 = TahunAjaran::factory()->create(['tahun_ajaran' => '2022/2023', 'semester' => 'ganjil']);
        $tahunAjaran2 = TahunAjaran::factory()->create(['tahun_ajaran' => '2022/2023', 'semester' => 'genap']);

        $matakuliah1 = Matakuliah::factory()->create(['id_pic' => Pic::first()->id, 'nama_matakuliah' => 'Algoritma', 'kode_matkul' => 'ALGO']);
        $matakuliah2 = Matakuliah::factory()->create(['id_pic' => Pic::first()->id, 'nama_matakuliah' => 'Basis Data', 'kode_matkul' => 'DB']);

        $mapping1 = MappingKelasMatakuliah::factory()->create([
            'id_matakuliah' => $matakuliah1->id,
            'id_tahun_ajaran' => $tahunAjaran1->id,
            'id_program_studi' => $programStudi->id,
            'nama_kelas' => 'A',
            'kuota' => 50,
            'team_teaching' => false,
        ]);
        $mapping2 = MappingKelasMatakuliah::factory()->create([
            'id_matakuliah' => $matakuliah2->id,
            'id_tahun_ajaran' => $tahunAjaran2->id,
            'id_program_studi' => $programStudi->id,
            'nama_kelas' => 'B',
            'kuota' => 40,
            'team_teaching' => true,
        ]);

        PlottinganPengajaran::create([
            'id_dosen' => $dosen->id,
            'id_mapping_kelas_matakuliah' => $mapping1->id,
            'beban_sks' => $matakuliah1->sks,
        ]);
        PlottinganPengajaran::create([
            'id_dosen' => $dosen->id,
            'id_mapping_kelas_matakuliah' => $mapping2->id,
            'beban_sks' => $matakuliah2->sks / 2,
        ]);

        // 2. Aksi: Login sebagai Kaprodi dan kirim GET request
        // Endpoint: /api/v1/plottingan-pengajaran/dosen/{id_dosen}/riwayat-pengajaran
        $response = $this->actingAs($kaprodiUser, 'sanctum')->getJson('/api/v1/plottingan-pengajaran/dosen/' . $dosen->id . '/riwayat-pengajaran');

        // 3. Assertions
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Riwayat pengajaran untuk dosen ' . $dosen->name . ' berhasil dimuat.',
                 ]);

        $response->assertJsonCount(2, 'data.data'); // Ada 2 riwayat
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'current_page',
                'data' => [
                    '*' => [
                        'nama_matakuliah',
                        'pic_matakuliah',
                        'online_onsite',
                        'kelas',
                        'kuota',
                        'periode',
                    ]
                ],
                'total', 'per_page', // Struktur paginasi lainnya
            ]
        ]);

        $response->assertJsonFragment([
            'nama_matakuliah' => 'Algoritma',
            'kelas' => 'A',
            'periode' => '2022/2023 - ganjil',
        ]);
        $response->assertJsonFragment([
            'nama_matakuliah' => 'Basis Data',
            'kelas' => 'B',
            'periode' => '2022/2023 - genap',
        ]);
    }

    /**
     * Test Case: Memastikan Kaprodi gagal melihat riwayat pengajaran jika dosen tidak ditemukan.
     * HTTP Response status code = 404 (Not Found)
     *
     * @return void
     */
    public function test_kaprodi_cannot_view_teaching_history_if_dosen_not_found(): void
    {
        // 1. Persiapan Data
        $programStudi = ProgramStudi::first();
        $kaprodiUser = $this->createProgramStudiUser($programStudi);
        $nonExistentDosenId = 9999;

        // 2. Aksi
        $response = $this->actingAs($kaprodiUser, 'sanctum')->getJson('/api/v1/plottingan-pengajaran/dosen/' . $nonExistentDosenId . '/riwayat-pengajaran');

        // 3. Assertions
        $response->assertStatus(404)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Dosen tidak ditemukan.',
                 ]);
    }



}
