<?php

namespace Tests\Feature\KetuaKK;

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
use App\Models\KoordinatorMatakuliah;
use App\Models\KelompokKeahlian; // Dibutuhkan untuk Ketua KK
use App\Models\JabatanStruktural;
use App\Models\Pic;

class RiwayatPengajaranTest extends TestCase
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

        // Buat role dasar yang dibutuhkan
        Role::firstOrCreate(['id' => 1, 'name' => 'Superadmin']);
        Role::firstOrCreate(['id' => 2, 'name' => 'ProgramStudi']);
        Role::firstOrCreate(['id' => 3, 'name' => 'KelompokKeahlian']); // Role Ketua KK
        Role::firstOrCreate(['id' => 4, 'name' => 'LayananAkademik']);

        // Buat data dasar untuk Foreign Key Factories
        for ($i = 1; $i <= 10; $i++) {
            KelompokKeahlian::firstOrCreate(['id' => $i, 'nama' => 'KK ' . chr(64 + $i)]);
            JabatanStruktural::firstOrCreate(['id' => $i, 'nama' => 'Jabatan ' . $i, 'konversi_sks' => $i]);
        }
        Pic::firstOrCreate(['id' => 1, 'name' => 'Default PIC']);
        ProgramStudi::firstOrCreate(['id' => 1, 'nama' => 'S1 Informatika']); // Perlu ProgramStudi untuk DosenFactory
        TahunAjaran::firstOrCreate(['id' => 1, 'tahun_ajaran' => '2024/2025', 'semester' => 'ganjil']);
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
            'roleable_type' => KelompokKeahlian::class, // Roleable type untuk Ketua KK
            'roleable_id' => $kelompokKeahlian->id,
        ]);
        return $user;
    }

    /**
     * Test Case: Memastikan Ketua KK dapat melihat riwayat pengajaran dosen.
     * HTTP Response status code = 200 (Success)
     *
     * @return void
     */
    public function test_ketua_kk_can_view_dosen_teaching_history_successfully(): void
    {
        // 1. Persiapan Data
        $kelompokKeahlian = KelompokKeahlian::first();
        $ketuaKKUser = $this->createKetuaKKUser($kelompokKeahlian);
        $dosen = Dosen::factory()->create(['id_kelompok_keahlian' => $kelompokKeahlian->id]); // Dosen yang riwayatnya akan dilihat

        $tahunAjaran1 = TahunAjaran::factory()->create(['tahun_ajaran' => '2022/2023', 'semester' => 'ganjil']);
        $tahunAjaran2 = TahunAjaran::factory()->create(['tahun_ajaran' => '2022/2023', 'semester' => 'genap']);

        // Buat PIC yang namanya cocok dengan Kelompok Keahlian Ketua KK
        $picMatchingKK = Pic::firstOrCreate(['name' => $kelompokKeahlian->nama]);
        $matakuliah1 = Matakuliah::factory()->create(['id_pic' => $picMatchingKK->id, 'nama_matakuliah' => 'Algoritma', 'kode_matkul' => 'ALGO']);
        $matakuliah2 = Matakuliah::factory()->create(['id_pic' => $picMatchingKK->id, 'nama_matakuliah' => 'Basis Data', 'kode_matkul' => 'DB']);

        $programStudi = ProgramStudi::first();

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

        // 2. Aksi: Login sebagai Ketua KK dan kirim GET request
        // Endpoint: /api/v1/plottingan-pengajaran/dosen/{id_dosen}/riwayat-pengajaran
        $response = $this->actingAs($ketuaKKUser, 'sanctum')->getJson('/api/v1/plottingan-pengajaran/dosen/' . $dosen->id . '/riwayat-pengajaran');

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
     * Test Case: Memastikan Ketua KK gagal melihat riwayat pengajaran jika dosen tidak ditemukan.
     * HTTP Response status code = 404 (Not Found)
     *
     * @return void
     */
    public function test_ketua_kk_cannot_view_teaching_history_if_dosen_not_found(): void
    {
        // 1. Persiapan Data
        $kelompokKeahlian = KelompokKeahlian::first();
        $ketuaKKUser = $this->createKetuaKKUser($kelompokKeahlian);
        $nonExistentDosenId = 9999;

        // 2. Aksi
        $response = $this->actingAs($ketuaKKUser, 'sanctum')->getJson('/api/v1/plottingan-pengajaran/dosen/' . $nonExistentDosenId . '/riwayat-pengajaran');

        // 3. Assertions
        $response->assertStatus(404)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Dosen tidak ditemukan.',
                 ]);
    }

}
