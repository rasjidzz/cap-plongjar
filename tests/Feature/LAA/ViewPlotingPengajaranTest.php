<?php

namespace Tests\Feature\LAA; // Perhatikan namespace ini

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
use App\Models\KelompokKeahlian;
use App\Models\JabatanStruktural;
use App\Models\Pic;
use Illuminate\Pagination\LengthAwarePaginator; // Import LengthAwarePaginator

class ViewPlotinganPengajaranTest extends TestCase
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
        Role::firstOrCreate(['id' => 3, 'name' => 'KelompokKeahlian']);
        Role::firstOrCreate(['id' => 4, 'name' => 'LayananAkademik']); // Role Layanan Akademik
        Role::firstOrCreate(['id' => 5, 'name' => 'KepalaUrusanLab']); // Role lain yang punya akses

        for ($i = 1; $i <= 10; $i++) {
            KelompokKeahlian::firstOrCreate(['id' => $i, 'nama' => 'KK ' . chr(64 + $i)]);
            JabatanStruktural::firstOrCreate(['id' => $i, 'nama' => 'Jabatan ' . $i, 'konversi_sks' => $i]);
        }
        Pic::firstOrCreate(['id' => 1, 'name' => 'Default PIC']);
        ProgramStudi::firstOrCreate(['id' => 1, 'nama' => 'S1 Informatika']);
        TahunAjaran::firstOrCreate(['id' => 1, 'tahun_ajaran' => '2024/2025', 'semester' => 'ganjil']);
    }

    /**
     * Helper method untuk membuat user Layanan Akademik (LAA).
     *
     * @return \App\Models\User
     */
    protected function createLAAUser()
    {
        $user = User::factory()->create();
        $laaRole = Role::where('name', 'LayananAkademik')->first();
        User_Role::create([
            'user_id' => $user->id,
            'role_id' => $laaRole->id,
            'roleable_type' => null,
            'roleable_id' => null,
        ]);
        return $user;
    }

    /**
     * Test Case: Memastikan LAA dapat melihat hasil plottingan pengajaran berdasarkan prodi dan tahun ajaran.
     * HTTP Response status code = 200 (Success)
     *
     * @return void
     */
    public function test_laa_can_view_plotting_results_by_prodi_and_tahun_ajaran_successfully(): void
    {
        // 1. Persiapan Data
        $laaUser = $this->createLAAUser();
        $tahunAjaran = TahunAjaran::factory()->create(['tahun_ajaran' => '2023/2024', 'semester' => 'genap']);
        $programStudiTarget = ProgramStudi::factory()->create(['nama' => 'S1 Teknik Informatika']);
        $programStudiOther = ProgramStudi::factory()->create(['nama' => 'S1 Fisika']);

        // Buat PIC dan Dosen
        $pic = Pic::first();
        $dosen1 = Dosen::factory()->create(['id_kelompok_keahlian' => KelompokKeahlian::first()->id]);
        $dosen2 = Dosen::factory()->create(['id_kelompok_keahlian' => KelompokKeahlian::first()->id]);
        $dosenKoordinator = Dosen::factory()->create(['id_kelompok_keahlian' => KelompokKeahlian::first()->id]);

        // Buat Mata Kuliah
        $matakuliah1 = Matakuliah::factory()->create(['id_pic' => $pic->id, 'sks' => 3]);
        $matakuliah2 = Matakuliah::factory()->create(['id_pic' => $pic->id, 'sks' => 2]);

        // Buat Mapping Kelas Mata Kuliah untuk Program Studi TARGET
        $mapping1 = MappingKelasMatakuliah::factory()->create([
            'id_matakuliah' => $matakuliah1->id,
            'id_tahun_ajaran' => $tahunAjaran->id,
            'id_program_studi' => $programStudiTarget->id,
            'nama_kelas' => 'A',
            'team_teaching' => false,
        ]);
        $mapping2 = MappingKelasMatakuliah::factory()->create([
            'id_matakuliah' => $matakuliah2->id,
            'id_tahun_ajaran' => $tahunAjaran->id,
            'id_program_studi' => $programStudiTarget->id,
            'nama_kelas' => 'B',
            'team_teaching' => true,
        ]);

        // Kaitkan Koordinator dengan mapping
        KoordinatorMatakuliah::factory()->create([
            'id_dosen' => $dosenKoordinator->id,
            'id_mapping_kelas_matakuliah' => $mapping1->id,
        ]);

        // Buat Plottingan Pengajaran untuk Program Studi TARGET
        PlottinganPengajaran::create([
            'id_dosen' => $dosen1->id,
            'id_mapping_kelas_matakuliah' => $mapping1->id,
            'beban_sks' => 3,
        ]);
        PlottinganPengajaran::create([
            'id_dosen' => $dosen2->id,
            'id_mapping_kelas_matakuliah' => $mapping2->id,
            'beban_sks' => 1, // Beban SKS untuk team teaching
        ]);

        // Buat plottingan untuk Program Studi LAIN (agar tidak ikut terambil)
        $matakuliahLain = Matakuliah::factory()->create(['id_pic' => $pic->id, 'sks' => 4]);
        $mappingLain = MappingKelasMatakuliah::factory()->create([
            'id_matakuliah' => $matakuliahLain->id,
            'id_tahun_ajaran' => $tahunAjaran->id,
            'id_program_studi' => $programStudiOther->id,
        ]);
        PlottinganPengajaran::create([
            'id_dosen' => Dosen::factory()->create(['id_kelompok_keahlian' => KelompokKeahlian::first()->id])->id,
            'id_mapping_kelas_matakuliah' => $mappingLain->id,
            'beban_sks' => 4,
        ]);

        // 2. Aksi: Login sebagai LAA dan kirim GET request
        // Endpoint: /api/v1/plottingan-pengajaran/tahun-ajaran/{id_tahun_ajaran}/program-studi/{id_program_studi}
        $response = $this->actingAs($laaUser, 'sanctum')->getJson(
            '/api/v1/plottingan-pengajaran/tahun-ajaran/' . $tahunAjaran->id . '/program-studi/' . $programStudiTarget->id
        );

        // 3. Assertions
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Hasil Plottingan Pengajaran untuk Program Studi "' . $programStudiTarget->nama . '" pada Tahun Ajaran "' . $tahunAjaran->tahun_ajaran . ' - ' . $tahunAjaran->semester . '" berhasil dimuat.',
                 ]);

        $response->assertJsonCount(2, 'data.data'); // Memastikan ada 2 item plottingan untuk prodi target

        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'current_page',
                'data' => [
                    '*' => [
                        'id_plottingan',
                        'id_mapping_kelas',
                        'nama_matakuliah',
                        'kode_matakuliah',
                        'pic_matakuliah',
                        'sks_matakuliah',
                        'nama_kelas',
                        'dosen_pengajar',
                        'kode_dosen_pengajar',
                        'beban_sks_dosen',
                        'koordinator_matakuliah',
                        'kode_koordinator',
                        'tahun_ajaran',
                        'mandatory_status',
                        'tingkat_matakuliah',
                        'hour_target',
                        'mk_eksepsi',
                        'team_teaching',
                    ]
                ],
                'total', 'per_page', // Struktur paginasi lainnya
            ]
        ]);

        // Memverifikasi keberadaan data plottingan spesifik
        $response->assertJsonFragment([
            'nama_matakuliah' => $matakuliah1->nama_matakuliah,
            'kode_matakuliah' => $matakuliah1->kode_matkul,
            'pic_matakuliah' => $pic->name,
            'sks_matakuliah' => $matakuliah1->sks,
            'nama_kelas' => $mapping1->nama_kelas,
            'dosen_pengajar' => $dosen1->name,
            'kode_dosen_pengajar' => $dosen1->lecturer_code,
            'beban_sks_dosen' => 3,
            'koordinator_matakuliah' => $dosenKoordinator->name,
            'kode_koordinator' => $dosenKoordinator->lecturer_code,
            'tahun_ajaran' => $tahunAjaran->tahun_ajaran . ' - ' . $tahunAjaran->semester,
            'mandatory_status' => $matakuliah1->mandatory_status,
            'tingkat_matakuliah' => $matakuliah1->tingkat_matakuliah,
            'hour_target' => $matakuliah1->hour_target,
            'mk_eksepsi' => $matakuliah1->matakuliah_eksepsi,
            'team_teaching' => ($mapping1->team_teaching ? 'Yes' : 'No'),
        ]);

        // Memastikan plottingan dari prodi lain tidak ada dalam respons
        $response->assertJsonMissing([
            'nama_kelas' => $mappingLain->nama_kelas,
        ]);
    }

    /**
     * Test Case: Memastikan LAA gagal melihat hasil plottingan jika Tahun Ajaran tidak ditemukan.
     * HTTP Response status code = 404 (Not Found)
     *
     * @return void
     */
    public function test_laa_cannot_view_plotting_results_if_tahun_ajaran_not_found(): void
    {
        // 1. Persiapan Data
        $laaUser = $this->createLAAUser();
        $programStudi = ProgramStudi::factory()->create(['nama' => 'S1 Teknologi Informasi']);
        $nonExistentTahunAjaranId = 9999;

        // 2. Aksi
        $response = $this->actingAs($laaUser, 'sanctum')->getJson(
            '/api/v1/plottingan-pengajaran/tahun-ajaran/' . $nonExistentTahunAjaranId . '/program-studi/' . $programStudi->id
        );

        // 3. Assertions
        $response->assertStatus(404)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Tahun Ajaran tidak ditemukan.',
                 ]);
    }

    /**
     * Test Case: Memastikan LAA gagal melihat hasil plottingan jika Program Studi tidak ditemukan.
     * HTTP Response status code = 404 (Not Found)
     *
     * @return void
     */
    public function test_laa_cannot_view_plotting_results_if_program_studi_not_found(): void
    {
        // 1. Persiapan Data
        $laaUser = $this->createLAAUser();
        $tahunAjaran = TahunAjaran::factory()->create(['tahun_ajaran' => '2024/2025', 'semester' => 'ganjil']);
        $nonExistentProgramStudiId = 8888;

        // 2. Aksi
        $response = $this->actingAs($laaUser, 'sanctum')->getJson(
            '/api/v1/plottingan-pengajaran/tahun-ajaran/' . $tahunAjaran->id . '/program-studi/' . $nonExistentProgramStudiId
        );

        // 3. Assertions
        $response->assertStatus(404)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Program Studi tidak ditemukan.',
                 ]);
    }
}
