<?php

namespace Tests\Feature\Integration;

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

class IntegrationTestKapLaa extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['id' => 1, 'name' => 'Superadmin']);
        Role::firstOrCreate(['id' => 2, 'name' => 'ProgramStudi']);
        Role::firstOrCreate(['id' => 3, 'name' => 'KelompokKeahlian']);
        Role::firstOrCreate(['id' => 4, 'name' => 'LayananAkademik']);
        Role::firstOrCreate(['id' => 5, 'name' => 'KepalaUrusanLab']);

        for ($i = 1; $i <= 10; $i++) {
            KelompokKeahlian::firstOrCreate(['id' => $i, 'nama' => 'KK ' . chr(64 + $i)]);
            JabatanStruktural::firstOrCreate(['id' => $i, 'nama' => 'Jabatan ' . $i, 'konversi_sks' => $i]);
        }
        Pic::firstOrCreate(['id' => 1, 'name' => 'Default PIC']);
        ProgramStudi::firstOrCreate(['id' => 1, 'nama' => 'S1 Informatika']);
        TahunAjaran::firstOrCreate(['id' => 1, 'tahun_ajaran' => '2024/2025', 'semester' => 'ganjil']);
    }

    public function createProgramStudiUser(ProgramStudi $programStudi)
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

    public function createLAAUser()
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

    public function test_kaprodi_plots_solo_with_jabatan_and_laa_views_results(): void
    {

        $programStudiKaprodi = ProgramStudi::factory()->create(['nama' => 'S1 Teknik Komputer']);
        $kaprodiUser = $this->createProgramStudiUser($programStudiKaprodi);
        $laaUser = $this->createLAAUser();

        $dosenPengajar = Dosen::factory()->create([
            'id_kelompok_keahlian' => KelompokKeahlian::first()->id,
            'id_jabatan_struktural' => JabatanStruktural::first()->id, // Dosen memiliki jabatan struktural
        ]);
        $dosenKoordinator = Dosen::factory()->create(['id_kelompok_keahlian' => KelompokKeahlian::first()->id]);

        $tahunAjaran = TahunAjaran::factory()->create(['tahun_ajaran' => '2024/2025', 'semester' => 'ganjil']);

        // PIC name harus cocok dengan nama Program Studi Kaprodi untuk otorisasi plotting
        $picMatchingProdi = Pic::firstOrCreate(['name' => $programStudiKaprodi->nama]);
        $matakuliah = Matakuliah::factory()->create(['id_pic' => $picMatchingProdi->id, 'sks' => 3]);

        $mappingKelas = MappingKelasMatakuliah::factory()->create([
            'id_matakuliah' => $matakuliah->id,
            'id_tahun_ajaran' => $tahunAjaran->id,
            'id_program_studi' => $programStudiKaprodi->id,
            'nama_kelas' => 'SoloJabatanA',
            'team_teaching' => false,
            'kuota' => 50,
        ]);

        KoordinatorMatakuliah::factory()->create([
            'id_dosen' => $dosenKoordinator->id,
            'id_mapping_kelas_matakuliah' => $mappingKelas->id,
        ]);

        $bebanSksPlotting = $matakuliah->sks; // Untuk solo teaching, beban SKS = SKS mata kuliah

        // 2. Aksi 1: Kaprodi melakukan plotting
        $kaprodiPlotResponse = $this->actingAs($kaprodiUser, 'sanctum')->postJson('/api/v1/plottingan-pengajaran/start-plottingan-pengajaran', [
            'id_dosen' => $dosenPengajar->id,
            'id_mapping_kelas_matakuliah' => $mappingKelas->id,
            'beban_sks' => $bebanSksPlotting,
            'id_program_studi' => $programStudiKaprodi->id,
        ]);

        // Assert Kaprodi plotting berhasil
        $kaprodiPlotResponse->assertStatus(201)
                            ->assertJson(['success' => true, 'message' => 'Plottingan pengajaran berhasil disimpan.']);
        $this->assertDatabaseHas('plottingan_pengajarans', [
            'id_dosen' => $dosenPengajar->id,
            'id_mapping_kelas_matakuliah' => $mappingKelas->id,
            'beban_sks' => $bebanSksPlotting,
        ]);

        $laaViewResponse = $this->actingAs($laaUser, 'sanctum')->getJson(
            '/api/v1/plottingan-pengajaran/tahun-ajaran/' . $tahunAjaran->id . '/program-studi/' . $programStudiKaprodi->id
        );

        // 4. Assert LAA view berhasil dan data sesuai
        $laaViewResponse->assertStatus(200)
                        ->assertJson([
                            'success' => true,
                            'message' => 'Hasil Plottingan Pengajaran untuk Program Studi "' . $programStudiKaprodi->nama . '" pada Tahun Ajaran "' . $tahunAjaran->tahun_ajaran . ' - ' . $tahunAjaran->semester . '" berhasil dimuat.',
                        ]);
        $laaViewResponse->assertJsonCount(1, 'data.data');
        $laaViewResponse->assertJsonFragment([
            'nama_matakuliah' => $matakuliah->nama_matakuliah,
            'nama_kelas' => $mappingKelas->nama_kelas,
            'dosen_pengajar' => $dosenPengajar->name,
            'beban_sks_dosen' => $bebanSksPlotting,
            'koordinator_matakuliah' => $dosenKoordinator->name,
            'team_teaching' => 'No',
        ]);
    }

    /**
     * Test Integrasi: Kaprodi memplot dosen (Solo Teaching, tanpa Jabatan Struktural) dan LAA melihat hasilnya.
     *
     * @return void
     */
    public function test_kaprodi_plots_solo_no_jabatan_and_laa_views_results(): void
    {
        // 1. Persiapan Data
        $programStudiKaprodi = ProgramStudi::factory()->create(['nama' => 'S1 Ilmu Komputer']);
        $kaprodiUser = $this->createProgramStudiUser($programStudiKaprodi);
        $laaUser = $this->createLAAUser();

        $dosenPengajar = Dosen::factory()->create([
            'id_kelompok_keahlian' => KelompokKeahlian::first()->id,
            'id_jabatan_struktural' => null, // Tanpa Jabatan Struktural
        ]);
        $dosenKoordinator = Dosen::factory()->create(['id_kelompok_keahlian' => KelompokKeahlian::first()->id]);

        $tahunAjaran = TahunAjaran::factory()->create(['tahun_ajaran' => '2025/2026', 'semester' => 'genap']);

        $picMatchingProdi = Pic::firstOrCreate(['name' => $programStudiKaprodi->nama]);
        $matakuliah = Matakuliah::factory()->create(['id_pic' => $picMatchingProdi->id, 'sks' => 4]);

        $mappingKelas = MappingKelasMatakuliah::factory()->create([
            'id_matakuliah' => $matakuliah->id,
            'id_tahun_ajaran' => $tahunAjaran->id,
            'id_program_studi' => $programStudiKaprodi->id,
            'nama_kelas' => 'SoloNoJabatanB',
            'team_teaching' => false, // Solo Teaching
            'kuota' => 50,
        ]);

        KoordinatorMatakuliah::factory()->create([
            'id_dosen' => $dosenKoordinator->id,
            'id_mapping_kelas_matakuliah' => $mappingKelas->id,
        ]);

        $bebanSksPlotting = $matakuliah->sks;

        // 2. Aksi 1: Kaprodi melakukan plotting
        $kaprodiPlotResponse = $this->actingAs($kaprodiUser, 'sanctum')->postJson('/api/v1/plottingan-pengajaran/start-plottingan-pengajaran', [
            'id_dosen' => $dosenPengajar->id,
            'id_mapping_kelas_matakuliah' => $mappingKelas->id,
            'beban_sks' => $bebanSksPlotting,
            'id_program_studi' => $programStudiKaprodi->id,
        ]);

        // Assert Kaprodi plotting berhasil
        $kaprodiPlotResponse->assertStatus(201)
                            ->assertJson(['success' => true, 'message' => 'Plottingan pengajaran berhasil disimpan.']);
        $this->assertDatabaseHas('plottingan_pengajarans', [
            'id_dosen' => $dosenPengajar->id,
            'id_mapping_kelas_matakuliah' => $mappingKelas->id,
            'beban_sks' => $bebanSksPlotting,
        ]);

        // 3. Aksi 2: LAA melihat hasil plottingan
        $laaViewResponse = $this->actingAs($laaUser, 'sanctum')->getJson(
            '/api/v1/plottingan-pengajaran/tahun-ajaran/' . $tahunAjaran->id . '/program-studi/' . $programStudiKaprodi->id
        );

        // 4. Assert LAA view berhasil dan data sesuai
        $laaViewResponse->assertStatus(200)
                        ->assertJson([
                            'success' => true,
                            'message' => 'Hasil Plottingan Pengajaran untuk Program Studi "' . $programStudiKaprodi->nama . '" pada Tahun Ajaran "' . $tahunAjaran->tahun_ajaran . ' - ' . $tahunAjaran->semester . '" berhasil dimuat.',
                        ]);
        $laaViewResponse->assertJsonCount(1, 'data.data');
        $laaViewResponse->assertJsonFragment([
            'nama_matakuliah' => $matakuliah->nama_matakuliah,
            'nama_kelas' => $mappingKelas->nama_kelas,
            'dosen_pengajar' => $dosenPengajar->name,
            'beban_sks_dosen' => $bebanSksPlotting,
            'koordinator_matakuliah' => $dosenKoordinator->name,
            'team_teaching' => 'No',
        ]);
    }

    /**
     * Test Integrasi: Kaprodi memplot dosen (Team Teaching, dengan Jabatan Struktural) dan LAA melihat hasilnya.
     *
     * @return void
     */
    public function test_kaprodi_plots_team_with_jabatan_and_laa_views_results(): void
    {
        // 1. Persiapan Data
        $programStudiKaprodi = ProgramStudi::factory()->create(['nama' => 'S1 Sistem Informasi']);
        $kaprodiUser = $this->createProgramStudiUser($programStudiKaprodi);
        $laaUser = $this->createLAAUser();

        $dosenPengajar = Dosen::factory()->create([
            'id_kelompok_keahlian' => KelompokKeahlian::first()->id,
            'id_jabatan_struktural' => JabatanStruktural::first()->id, // Dosen memiliki jabatan struktural
        ]);
        $dosenKoordinator = Dosen::factory()->create(['id_kelompok_keahlian' => KelompokKeahlian::first()->id]);

        $tahunAjaran = TahunAjaran::factory()->create(['tahun_ajaran' => '2026/2027', 'semester' => 'ganjil']);

        $picMatchingProdi = Pic::firstOrCreate(['name' => $programStudiKaprodi->nama]);
        $matakuliah = Matakuliah::factory()->create(['id_pic' => $picMatchingProdi->id, 'sks' => 6]); // MK 6 SKS

        $mappingKelas = MappingKelasMatakuliah::factory()->create([
            'id_matakuliah' => $matakuliah->id,
            'id_tahun_ajaran' => $tahunAjaran->id,
            'id_program_studi' => $programStudiKaprodi->id,
            'nama_kelas' => 'TeamJabatanC',
            'team_teaching' => true, // Team Teaching
            'kuota' => 50,
        ]);

        KoordinatorMatakuliah::factory()->create([
            'id_dosen' => $dosenKoordinator->id,
            'id_mapping_kelas_matakuliah' => $mappingKelas->id,
        ]);

        $bebanSksPlotting = 3; // Beban SKS untuk team teaching

        // 2. Aksi 1: Kaprodi melakukan plotting
        $kaprodiPlotResponse = $this->actingAs($kaprodiUser, 'sanctum')->postJson('/api/v1/plottingan-pengajaran/start-plottingan-pengajaran', [
            'id_dosen' => $dosenPengajar->id,
            'id_mapping_kelas_matakuliah' => $mappingKelas->id,
            'beban_sks' => $bebanSksPlotting,
            'id_program_studi' => $programStudiKaprodi->id,
        ]);

        // Assert Kaprodi plotting berhasil
        $kaprodiPlotResponse->assertStatus(201)
                            ->assertJson(['success' => true, 'message' => 'Plottingan pengajaran berhasil disimpan.']);
        $this->assertDatabaseHas('plottingan_pengajarans', [
            'id_dosen' => $dosenPengajar->id,
            'id_mapping_kelas_matakuliah' => $mappingKelas->id,
            'beban_sks' => $bebanSksPlotting,
        ]);

        // 3. Aksi 2: LAA melihat hasil plottingan
        $laaViewResponse = $this->actingAs($laaUser, 'sanctum')->getJson(
            '/api/v1/plottingan-pengajaran/tahun-ajaran/' . $tahunAjaran->id . '/program-studi/' . $programStudiKaprodi->id
        );

        // 4. Assert LAA view berhasil dan data sesuai
        $laaViewResponse->assertStatus(200)
                        ->assertJson([
                            'success' => true,
                            'message' => 'Hasil Plottingan Pengajaran untuk Program Studi "' . $programStudiKaprodi->nama . '" pada Tahun Ajaran "' . $tahunAjaran->tahun_ajaran . ' - ' . $tahunAjaran->semester . '" berhasil dimuat.',
                        ]);
        $laaViewResponse->assertJsonCount(1, 'data.data');
        $laaViewResponse->assertJsonFragment([
            'nama_matakuliah' => $matakuliah->nama_matakuliah,
            'nama_kelas' => $mappingKelas->nama_kelas,
            'dosen_pengajar' => $dosenPengajar->name,
            'beban_sks_dosen' => $bebanSksPlotting,
            'koordinator_matakuliah' => $dosenKoordinator->name,
            'team_teaching' => 'Yes',
        ]);
    }

    /**
     * Test Integrasi: Kaprodi memplot dosen (Team Teaching, tanpa Jabatan Struktural) dan LAA melihat hasilnya.
     *
     * @return void
     */
    public function test_kaprodi_plots_team_no_jabatan_and_laa_views_results(): void
    {
        // 1. Persiapan Data
        $programStudiKaprodi = ProgramStudi::factory()->create(['nama' => 'S1 Teknik Elektro']);
        $kaprodiUser = $this->createProgramStudiUser($programStudiKaprodi);
        $laaUser = $this->createLAAUser();

        $dosenPengajar = Dosen::factory()->create([
            'id_kelompok_keahlian' => KelompokKeahlian::first()->id,
            'id_jabatan_struktural' => null, // Tanpa Jabatan Struktural
        ]);
        $dosenKoordinator = Dosen::factory()->create(['id_kelompok_keahlian' => KelompokKeahlian::first()->id]);

        $tahunAjaran = TahunAjaran::factory()->create(['tahun_ajaran' => '2027/2028', 'semester' => 'ganjil']);

        $picMatchingProdi = Pic::firstOrCreate(['name' => $programStudiKaprodi->nama]);
        $matakuliah = Matakuliah::factory()->create(['id_pic' => $picMatchingProdi->id, 'sks' => 5]); // MK 5 SKS

        $mappingKelas = MappingKelasMatakuliah::factory()->create([
            'id_matakuliah' => $matakuliah->id,
            'id_tahun_ajaran' => $tahunAjaran->id,
            'id_program_studi' => $programStudiKaprodi->id,
            'nama_kelas' => 'TeamNoJabatanD',
            'team_teaching' => true, // Team Teaching
            'kuota' => 50,
        ]);

        KoordinatorMatakuliah::factory()->create([
            'id_dosen' => $dosenKoordinator->id,
            'id_mapping_kelas_matakuliah' => $mappingKelas->id,
        ]);

        $bebanSksPlotting = 2; // Beban SKS untuk team teaching

        // 2. Aksi 1: Kaprodi melakukan plotting
        $kaprodiPlotResponse = $this->actingAs($kaprodiUser, 'sanctum')->postJson('/api/v1/plottingan-pengajaran/start-plottingan-pengajaran', [
            'id_dosen' => $dosenPengajar->id,
            'id_mapping_kelas_matakuliah' => $mappingKelas->id,
            'beban_sks' => $bebanSksPlotting,
            'id_program_studi' => $programStudiKaprodi->id,
        ]);

        // Assert Kaprodi plotting berhasil
        $kaprodiPlotResponse->assertStatus(201)
                            ->assertJson(['success' => true, 'message' => 'Plottingan pengajaran berhasil disimpan.']);
        $this->assertDatabaseHas('plottingan_pengajarans', [
            'id_dosen' => $dosenPengajar->id,
            'id_mapping_kelas_matakuliah' => $mappingKelas->id,
            'beban_sks' => $bebanSksPlotting,
        ]);

        // 3. Aksi 2: LAA melihat hasil plottingan
        $laaViewResponse = $this->actingAs($laaUser, 'sanctum')->getJson(
            '/api/v1/plottingan-pengajaran/tahun-ajaran/' . $tahunAjaran->id . '/program-studi/' . $programStudiKaprodi->id
        );

        // 4. Assert LAA view berhasil dan data sesuai
        $laaViewResponse->assertStatus(200)
                        ->assertJson([
                            'success' => true,
                            'message' => 'Hasil Plottingan Pengajaran untuk Program Studi "' . $programStudiKaprodi->nama . '" pada Tahun Ajaran "' . $tahunAjaran->tahun_ajaran . ' - ' . $tahunAjaran->semester . '" berhasil dimuat.',
                        ]);
        $laaViewResponse->assertJsonCount(1, 'data.data');
        $laaViewResponse->assertJsonFragment([
            'nama_matakuliah' => $matakuliah->nama_matakuliah,
            'nama_kelas' => $mappingKelas->nama_kelas,
            'dosen_pengajar' => $dosenPengajar->name,
            'beban_sks_dosen' => $bebanSksPlotting,
            'koordinator_matakuliah' => $dosenKoordinator->name,
            'team_teaching' => 'Yes',
        ]);
    }
}
