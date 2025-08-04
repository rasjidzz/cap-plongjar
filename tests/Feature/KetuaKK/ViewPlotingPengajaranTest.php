<?php

namespace Tests\Feature\KetuaKK; // Perhatikan namespace ini

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

class ViewPlotingPengajaranTest extends TestCase
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

        for ($i = 1; $i <= 10; $i++) {
            KelompokKeahlian::firstOrCreate(['id' => $i, 'nama' => 'KK ' . chr(64 + $i)]);
            JabatanStruktural::firstOrCreate(['id' => $i, 'nama' => 'Jabatan ' . $i, 'konversi_sks' => $i]);
        }
        Pic::firstOrCreate(['id' => 1, 'name' => 'Default PIC']);
        ProgramStudi::firstOrCreate(['id' => 1, 'nama' => 'S1 Informatika']);
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
     * Test Case: Memastikan Ketua KK dapat melihat hasil plottingan pengajaran berdasarkan tahun ajaran.
     * HTTP Response status code = 200 (Success)
     *
     * @return void
     */
    public function test_ketua_kk_can_view_plotting_results_by_tahun_ajaran_successfully(): void
    {
        // 1. Persiapan Data
        $kelompokKeahlian = KelompokKeahlian::first();
        $ketuaKKUser = $this->createKetuaKKUser($kelompokKeahlian);
        $tahunAjaran = TahunAjaran::factory()->create(['tahun_ajaran' => '2023/2024', 'semester' => 'genap']);
        $programStudi = ProgramStudi::first();

        // Buat PIC yang namanya cocok dengan Kelompok Keahlian Ketua KK
        $picMatchingKK = Pic::firstOrCreate(['name' => $kelompokKeahlian->nama]);

        // Buat Dosen, Mata Kuliah, Mapping, dan Plottingan untuk tahun ajaran ini
        $dosen1 = Dosen::factory()->create(['id_kelompok_keahlian' => $kelompokKeahlian->id]);
        $matakuliah1 = Matakuliah::factory()->create(['id_pic' => $picMatchingKK->id, 'sks' => 3]);
        $mapping1 = MappingKelasMatakuliah::factory()->create([
            'id_matakuliah' => $matakuliah1->id,
            'id_tahun_ajaran' => $tahunAjaran->id,
            'id_program_studi' => $programStudi->id,
            'nama_kelas' => 'A',
            'team_teaching' => false,
        ]);
        PlottinganPengajaran::create([
            'id_dosen' => $dosen1->id,
            'id_mapping_kelas_matakuliah' => $mapping1->id,
            'beban_sks' => 3,
        ]);

        $dosenKoordinator = Dosen::factory()->create(['id_kelompok_keahlian' => $kelompokKeahlian->id]);
        KoordinatorMatakuliah::factory()->create([
            'id_dosen' => $dosenKoordinator->id,
            'id_mapping_kelas_matakuliah' => $mapping1->id,
        ]);

        // Buat plottingan untuk tahun ajaran LAIN (agar tidak ikut terambil)
        $tahunAjaranLain = TahunAjaran::factory()->create(['tahun_ajaran' => '2022/2023', 'semester' => 'ganjil']);
        $matakuliahLain = Matakuliah::factory()->create(['id_pic' => Pic::first()->id, 'sks' => 2]);
        $mappingLain = MappingKelasMatakuliah::factory()->create([
            'id_matakuliah' => $matakuliahLain->id,
            'id_tahun_ajaran' => $tahunAjaranLain->id,
            'id_program_studi' => $programStudi->id,
        ]);
        PlottinganPengajaran::create([
            'id_dosen' => Dosen::factory()->create(['id_kelompok_keahlian' => KelompokKeahlian::first()->id])->id,
            'id_mapping_kelas_matakuliah' => $mappingLain->id,
            'beban_sks' => 2,
        ]);

        // 2. Aksi: Login sebagai Ketua KK dan kirim GET request
        // Endpoint: /api/v1/plottingan-pengajaran/get-hasil-plottingan-pengajaran/{id_tahun_ajaran}
        $response = $this->actingAs($ketuaKKUser, 'sanctum')->getJson('/api/v1/plottingan-pengajaran/get-hasil-plottingan-pengajaran/' . $tahunAjaran->id);

        // 3. Assertions
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Hasil Plottingan Pengajaran berhasil dimuat.',
                 ]);

        $response->assertJsonCount(1, 'data'); // Hanya ada 1 plottingan untuk tahun ajaran ini
        $response->assertJsonFragment([
            'kode_matakuliah' => $matakuliah1->kode_matkul,
            'nama_matakuliah' => $matakuliah1->nama_matakuliah,
            'pic' => $picMatchingKK->name,
            'kode_dosen_pengajar' => $dosen1->lecturer_code,
            'beban_sks_dosen_pengajar' => 3,
            'nama_kelas' => $mapping1->nama_kelas,
            'praktikum' => ($matakuliah1->praktikum ? 'Yes' : 'No'),
            'kode_dosen_koordinator' => $dosenKoordinator->lecturer_code,
            'tahun_ajaran' => $tahunAjaran->tahun_ajaran . ' - ' . $tahunAjaran->semester,
            'team_teaching_kelas' => ($mapping1->team_teaching ? 'Yes' : 'No'),
        ]);

        // Memastikan plottingan dari tahun ajaran lain tidak ada dalam respons
        $response->assertJsonMissing([
            'nama_matakuliah' => $matakuliahLain->nama_matakuliah,
        ]);
    }

    /**
     * Test Case: Memastikan Ketua KK mendapatkan daftar plottingan kosong jika tidak ada plottingan untuk tahun ajaran tersebut.
     * HTTP Response status code = 200 (Success)
     *
     * @return void
     */
    public function test_ketua_kk_gets_empty_plotting_results_if_no_records_for_tahun_ajaran(): void
    {
        // 1. Persiapan Data
        $kelompokKeahlian = KelompokKeahlian::first();
        $ketuaKKUser = $this->createKetuaKKUser($kelompokKeahlian);
        $tahunAjaran = TahunAjaran::factory()->create(['tahun_ajaran' => '2023/2024', 'semester' => 'ganjil']);
        // Tidak membuat plottingan apapun untuk tahun ajaran ini

        // 2. Aksi: Login sebagai Ketua KK dan kirim GET request
        $response = $this->actingAs($ketuaKKUser, 'sanctum')->getJson('/api/v1/plottingan-pengajaran/get-hasil-plottingan-pengajaran/' . $tahunAjaran->id);

        // 3. Assertions
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Tidak ada data plottingan pengajaran yang ditemukan untuk tahun ajaran ini.',
                     'data' => []
                 ]);
        $this->assertCount(0, $response->json('data'));
    }

    /**
     * Test Case: Memastikan Ketua KK gagal melihat hasil plottingan jika Tahun Ajaran tidak ditemukan.
     * HTTP Response status code = 404 (Not Found)
     *
     * @return void
     */
    public function test_ketua_kk_cannot_view_plotting_results_if_tahun_ajaran_not_found(): void
    {
        // 1. Persiapan Data
        $kelompokKeahlian = KelompokKeahlian::first();
        $ketuaKKUser = $this->createKetuaKKUser($kelompokKeahlian);
        $nonExistentTahunAjaranId = 9999;

        // 2. Aksi
        $response = $this->actingAs($ketuaKKUser, 'sanctum')->getJson('/api/v1/plottingan-pengajaran/get-hasil-plottingan-pengajaran/' . $nonExistentTahunAjaranId);

        // 3. Assertions
        $response->assertStatus(404)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Tahun Ajaran tidak ditemukan.',
                     'data' => []
                 ]);
    }
}
