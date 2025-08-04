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
use App\Models\PlottinganPengajaran; // Import model PlottinganPengajaran
use App\Models\KoordinatorMatakuliah; // Import model KoordinatorMatakuliah
use App\Models\KelompokKeahlian; // Dibutuhkan oleh DosenFactory
use App\Models\Pic; // Dibutuhkan oleh MatakuliahFactory

class ViewPlotinganPengajaranTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Buat role dasar yang dibutuhkan
        Role::firstOrCreate(['id' => 1, 'name' => 'Superadmin']);
        Role::firstOrCreate(['id' => 2, 'name' => 'ProgramStudi']);
        Role::firstOrCreate(['id' => 3, 'name' => 'KelompokKeahlian']);
        Role::firstOrCreate(['id' => 4, 'name' => 'LayananAkademik']); // Untuk middleware route

        // Buat data dasar untuk Foreign Key
        // Dosen
        for ($i = 1; $i <= 5; $i++) {
            KelompokKeahlian::firstOrCreate(['id' => $i, 'nama' => 'KK ' . chr(64 + $i)]);
        }
        // Matakuliah
        Pic::firstOrCreate(['id' => 1, 'name' => 'Default PIC']);
        // ProgramStudi
        ProgramStudi::firstOrCreate(['id' => 1, 'nama' => 'S1 Informatika']);
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
     * Test Case: Memastikan Kaprodi dapat melihat hasil akhir dari plotting dosen di prodi mereka.
     * HTTP Response status code = 200 (Success)
     *
     * @return void
     */
    public function test_kaprodi_can_view_plotting_results_for_their_prodi(): void
    {
        // 1. Persiapan Data
        $tahunAjaran = TahunAjaran::factory()->create(['tahun_ajaran' => '2024/2025', 'semester' => 'ganjil']);
        $programStudiKaprodi = ProgramStudi::factory()->create(['nama' => 'S1 Teknik Informatika']);
        $kaprodiUser = $this->createProgramStudiUser($programStudiKaprodi);

        // Buat Dosen dan Mata Kuliah
        $dosen1 = Dosen::factory()->create(['id_kelompok_keahlian' => KelompokKeahlian::first()->id]);
        $dosen2 = Dosen::factory()->create(['id_kelompok_keahlian' => KelompokKeahlian::first()->id]);
        $matakuliah1 = Matakuliah::factory()->create(['id_pic' => Pic::first()->id, 'sks' => 3]);
        $matakuliah2 = Matakuliah::factory()->create(['id_pic' => Pic::first()->id, 'sks' => 2]);

        // Buat Koordinator Mata Kuliah (opsional, tapi jika relasi dimuat, sebaiknya ada)
        $dosenKoordinator = Dosen::factory()->create(['id_kelompok_keahlian' => KelompokKeahlian::first()->id]);
        $koordinatorMatakuliah = KoordinatorMatakuliah::factory()->create([
            'id_dosen' => $dosenKoordinator->id,
        ]);


        // Buat Mapping Kelas Mata Kuliah (harus cocok dengan Program Studi Kaprodi)
        $mapping1 = MappingKelasMatakuliah::factory()->create([
            'id_matakuliah' => $matakuliah1->id,
            'id_tahun_ajaran' => $tahunAjaran->id,
            'id_program_studi' => $programStudiKaprodi->id,
            'nama_kelas' => 'MK1-A',
            'kuota' => 50,
            'team_teaching' => false,
        ]);
        // Kaitkan KoordinatorMatakuliah dengan mapping
        $koordinatorMatakuliah->id_mapping_kelas_matakuliah = $mapping1->id;
        $koordinatorMatakuliah->save();


        $mapping2 = MappingKelasMatakuliah::factory()->create([
            'id_matakuliah' => $matakuliah2->id,
            'id_tahun_ajaran' => $tahunAjaran->id,
            'id_program_studi' => $programStudiKaprodi->id,
            'nama_kelas' => 'MK2-B',
            'kuota' => 40,
            'team_teaching' => true,
        ]);

        // Buat Plottingan Pengajaran untuk Program Studi Kaprodi
        PlottinganPengajaran::create([ // menggunakan create karena factory mungkin tidak spesifik
            'id_dosen' => $dosen1->id,
            'id_mapping_kelas_matakuliah' => $mapping1->id,
            'beban_sks' => $matakuliah1->sks,
        ]);
        PlottinganPengajaran::create([
            'id_dosen' => $dosen2->id,
            'id_mapping_kelas_matakuliah' => $mapping2->id,
            'beban_sks' => $matakuliah2->sks / 2, // Contoh jika team teaching
        ]);

        // Buat plottingan untuk Prodi LAIN (agar tidak ikut terambil)
        $programStudiLain = ProgramStudi::factory()->create(['nama' => 'S1 Pjj Informatika']);
        $mappingLain = MappingKelasMatakuliah::factory()->create([
            'id_matakuliah' => Matakuliah::factory()->create(['id_pic' => Pic::first()->id])->id,
            'id_tahun_ajaran' => $tahunAjaran->id,
            'id_program_studi' => $programStudiLain->id,
            'nama_kelas' => 'MK-Lain',
        ]);
        PlottinganPengajaran::create([
            'id_dosen' => Dosen::factory()->create(['id_kelompok_keahlian' => KelompokKeahlian::first()->id])->id,
            'id_mapping_kelas_matakuliah' => $mappingLain->id,
            'beban_sks' => 3,
        ]);


        // 2. Aksi: Login sebagai Kaprodi dan kirim GET request
        // Endpoint: /api/v1/plottingan-pengajaran/tahun-ajaran/{id_tahun_ajaran}/program-studi/{id_program_studi}
        $response = $this->actingAs($kaprodiUser, 'sanctum')->getJson(
            '/api/v1/plottingan-pengajaran/tahun-ajaran/' . $tahunAjaran->id . '/program-studi/' . $programStudiKaprodi->id
        );

        // 3. Assertions
        $response->assertStatus(200) // Memastikan status HTTP 200 (Success)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Hasil Plottingan Pengajaran untuk Program Studi "' . $programStudiKaprodi->nama . '" pada Tahun Ajaran "' . $tahunAjaran->tahun_ajaran . ' - ' . $tahunAjaran->semester . '" berhasil dimuat.',
                 ]);

        // Memverifikasi bahwa data yang dikembalikan adalah hasil plottingan yang relevan
        $response->assertJsonCount(2, 'data.data'); // Memastikan ada 2 item plottingan di halaman saat ini

        // Memastikan plottingan dari prodi lain tidak ada dalam respons
        $response->assertJsonMissing([
            'nama_kelas' => $mappingLain->nama_kelas,
        ]);

    }

    public function test_kaprodi_cannot_view_plotting_results_if_tahun_ajaran_not_found(): void
    {
        // 1. Persiapan Data
        $programStudiKaprodi = ProgramStudi::factory()->create(['nama' => 'S1 Data Sains']);
        $kaprodiUser = $this->createProgramStudiUser($programStudiKaprodi);
        $nonExistentTahunAjaranId = 9999;

        // 2. Aksi
        $response = $this->actingAs($kaprodiUser, 'sanctum')->getJson(
            '/api/v1/plottingan-pengajaran/tahun-ajaran/' . $nonExistentTahunAjaranId . '/program-studi/' . $programStudiKaprodi->id
        );

        // 3. Assertions
        $response->assertStatus(404)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Tahun Ajaran tidak ditemukan.',
                 ]);
    }

    public function test_kaprodi_cannot_view_plotting_results_if_program_studi_not_found(): void
    {
        // 1. Persiapan Data
        $tahunAjaran = TahunAjaran::factory()->create(['tahun_ajaran' => '2024/2025', 'semester' => 'ganjil']);
        $programStudiKaprodi = ProgramStudi::factory()->create(['nama' => 'S1 Rekayasa Perangkat Lunak']);
        $kaprodiUser = $this->createProgramStudiUser($programStudiKaprodi);
        $nonExistentProgramStudiId = 8888;

        // 2. Aksi
        $response = $this->actingAs($kaprodiUser, 'sanctum')->getJson(
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
