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

class IntegrationTestKetKur extends TestCase
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

    protected function createKaurLabUser()
    {
        $user = User::factory()->create();
        $kaurLabRole = Role::where('name', 'KepalaUrusanLab')->first();
        User_Role::create([
            'user_id' => $user->id,
            'role_id' => $kaurLabRole->id,
            'roleable_type' => null,
            'roleable_id' => null,
        ]);
        return $user;
    }

    public function test_ketua_kk_plots_and_kaur_lab_views_results_successfully(): void
    {
        // 1. Persiapan Data
        $kelompokKeahlianKetuaKK = KelompokKeahlian::factory()->create(['nama' => 'KK Test']);
        $ketuaKKUser = $this->createKetuaKKUser($kelompokKeahlianKetuaKK);
        $kaurLabUser = $this->createKaurLabUser();

        $dosenPengajar = Dosen::factory()->create([
            'id_kelompok_keahlian' => $kelompokKeahlianKetuaKK->id, // Dosen dari KK yang sama
            'id_jabatan_struktural' => JabatanStruktural::first()->id,
        ]);
        $dosenKoordinator = Dosen::factory()->create(['id_kelompok_keahlian' => KelompokKeahlian::first()->id]);

        $tahunAjaran = TahunAjaran::factory()->create(['tahun_ajaran' => '2024/2025', 'semester' => 'ganjil']);
        $programStudi = ProgramStudi::factory()->create(['nama' => 'S1 Informatika']); // Program Studi terkait

        // PIC name harus cocok dengan nama Kelompok Keahlian Ketua KK untuk otorisasi plotting
        $picMatchingKK = Pic::firstOrCreate(['name' => $kelompokKeahlianKetuaKK->nama]);
        $matakuliah = Matakuliah::factory()->create(['id_pic' => $picMatchingKK->id, 'sks' => 3]);

        $mappingKelas = MappingKelasMatakuliah::factory()->create([
            'id_matakuliah' => $matakuliah->id,
            'id_tahun_ajaran' => $tahunAjaran->id,
            'id_program_studi' => $programStudi->id,
            'nama_kelas' => 'KetuaKKPlotView',
            'team_teaching' => false,
            'kuota' => 50,
        ]);

        KoordinatorMatakuliah::factory()->create([
            'id_dosen' => $dosenKoordinator->id,
            'id_mapping_kelas_matakuliah' => $mappingKelas->id,
        ]);

        $bebanSksPlotting = $matakuliah->sks; // Untuk solo teaching

        // 2. Aksi 1: Ketua KK melakukan plotting
        $ketuaKKPlotResponse = $this->actingAs($ketuaKKUser, 'sanctum')->postJson('/api/v1/plottingan-pengajaran/start-plottingan-pengajaran', [
            'id_dosen' => $dosenPengajar->id,
            'id_mapping_kelas_matakuliah' => $mappingKelas->id,
            'beban_sks' => $bebanSksPlotting,
            'id_program_studi' => $programStudi->id, // Penting untuk otorisasi
        ]);

        // Assert Ketua KK plotting berhasil
        $ketuaKKPlotResponse->assertStatus(201)
                            ->assertJson(['success' => true, 'message' => 'Plottingan pengajaran berhasil disimpan.']);
        $this->assertDatabaseHas('plottingan_pengajarans', [
            'id_dosen' => $dosenPengajar->id,
            'id_mapping_kelas_matakuliah' => $mappingKelas->id,
            'beban_sks' => $bebanSksPlotting,
        ]);

        // 3. Aksi 2: Kaur Lab melihat hasil plottingan
        $laaViewResponse = $this->actingAs($kaurLabUser, 'sanctum')->getJson(
            '/api/v1/plottingan-pengajaran/tahun-ajaran/' . $tahunAjaran->id . '/program-studi/' . $programStudi->id
        );

        // 4. Assert LAA view berhasil dan data sesuai
        $laaViewResponse->assertStatus(200)
                        ->assertJson([
                            'success' => true,
                            'message' => 'Hasil Plottingan Pengajaran untuk Program Studi "' . $programStudi->nama . '" pada Tahun Ajaran "' . $tahunAjaran->tahun_ajaran . ' - ' . $tahunAjaran->semester . '" berhasil dimuat.',
                        ]);
        $laaViewResponse->assertJsonCount(1, 'data.data');
        $laaViewResponse->assertJsonFragment([
            'nama_matakuliah' => $matakuliah->nama_matakuliah,
            'kode_matakuliah' => $matakuliah->kode_matkul,
            'pic_matakuliah' => $picMatchingKK->name,
            'sks_matakuliah' => $matakuliah->sks,
            'nama_kelas' => $mappingKelas->nama_kelas,
            'dosen_pengajar' => $dosenPengajar->name,
            'kode_dosen_pengajar' => $dosenPengajar->lecturer_code,
            'beban_sks_dosen' => $bebanSksPlotting,
            'koordinator_matakuliah' => $dosenKoordinator->name,
            'kode_koordinator' => $dosenKoordinator->lecturer_code,
            'tahun_ajaran' => $tahunAjaran->tahun_ajaran . ' - ' . $tahunAjaran->semester,
            'mandatory_status' => $matakuliah->mandatory_status,
            'tingkat_matakuliah' => $matakuliah->tingkat_matakuliah,
            'hour_target' => $matakuliah->hour_target,
            'mk_eksepsi' => $matakuliah->matakuliah_eksepsi,
            'team_teaching' => ($mappingKelas->team_teaching ? 'Yes' : 'No'),
        ]);
    }

    /**
     * Test Integrasi: Ketua KK memplot dosen (Solo Teaching, tanpa Jabatan Struktural) dan Kaur Lab melihat hasilnya.
     *
     * @return void
     */
    public function test_ketua_kk_plots_solo_no_jabatan_and_kaur_lab_views_results(): void
    {
        // 1. Persiapan Data
        $kelompokKeahlianKetuaKK = KelompokKeahlian::factory()->create(['nama' => 'KK Tanpa Jabatan']);
        $ketuaKKUser = $this->createKetuaKKUser($kelompokKeahlianKetuaKK);
        $kaurLabUser = $this->createKaurLabUser();

        $dosenPengajar = Dosen::factory()->create([
            'id_kelompok_keahlian' => $kelompokKeahlianKetuaKK->id,
            'id_jabatan_struktural' => null, // Tanpa Jabatan Struktural
        ]);
        $dosenKoordinator = Dosen::factory()->create(['id_kelompok_keahlian' => KelompokKeahlian::first()->id]);

        $tahunAjaran = TahunAjaran::factory()->create(['tahun_ajaran' => '2025/2026', 'semester' => 'genap']);
        $programStudi = ProgramStudi::factory()->create(['nama' => 'S1 Sistem Informasi']);

        $picMatchingKK = Pic::firstOrCreate(['name' => $kelompokKeahlianKetuaKK->nama]);
        $matakuliah = Matakuliah::factory()->create(['id_pic' => $picMatchingKK->id, 'sks' => 4]);

        $mappingKelas = MappingKelasMatakuliah::factory()->create([
            'id_matakuliah' => $matakuliah->id,
            'id_tahun_ajaran' => $tahunAjaran->id,
            'id_program_studi' => $programStudi->id,
            'nama_kelas' => 'SoloNoJabatanB',
            'team_teaching' => false, // Solo Teaching
            'kuota' => 50,
        ]);

        KoordinatorMatakuliah::factory()->create([
            'id_dosen' => $dosenKoordinator->id,
            'id_mapping_kelas_matakuliah' => $mappingKelas->id,
        ]);

        $bebanSksPlotting = $matakuliah->sks;

        // 2. Aksi 1: Ketua KK melakukan plotting
        $ketuaKKPlotResponse = $this->actingAs($ketuaKKUser, 'sanctum')->postJson('/api/v1/plottingan-pengajaran/start-plottingan-pengajaran', [
            'id_dosen' => $dosenPengajar->id,
            'id_mapping_kelas_matakuliah' => $mappingKelas->id,
            'beban_sks' => $bebanSksPlotting,
            'id_program_studi' => $programStudi->id,
        ]);

        // Assert Ketua KK plotting berhasil
        $ketuaKKPlotResponse->assertStatus(201)
                            ->assertJson(['success' => true, 'message' => 'Plottingan pengajaran berhasil disimpan.']);
        $this->assertDatabaseHas('plottingan_pengajarans', [
            'id_dosen' => $dosenPengajar->id,
            'id_mapping_kelas_matakuliah' => $mappingKelas->id,
            'beban_sks' => $bebanSksPlotting,
        ]);

        // 3. Aksi 2: Kaur Lab melihat hasil plottingan
        $laaViewResponse = $this->actingAs($kaurLabUser, 'sanctum')->getJson(
            '/api/v1/plottingan-pengajaran/tahun-ajaran/' . $tahunAjaran->id . '/program-studi/' . $programStudi->id
        );

        // 4. Assert LAA view berhasil dan data sesuai
        $laaViewResponse->assertStatus(200)
                        ->assertJson([
                            'success' => true,
                            'message' => 'Hasil Plottingan Pengajaran untuk Program Studi "' . $programStudi->nama . '" pada Tahun Ajaran "' . $tahunAjaran->tahun_ajaran . ' - ' . $tahunAjaran->semester . '" berhasil dimuat.',
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
     * Test Integrasi: Ketua KK memplot dosen (Team Teaching, dengan Jabatan Struktural) dan Kaur Lab melihat hasilnya.
     *
     * @return void
     */
    public function test_ketua_kk_plots_team_with_jabatan_and_kaur_lab_views_results(): void
    {
        // 1. Persiapan Data
        $kelompokKeahlianKetuaKK = KelompokKeahlian::factory()->create(['nama' => 'KK Dengan Jabatan']);
        $ketuaKKUser = $this->createKetuaKKUser($kelompokKeahlianKetuaKK);
        $kaurLabUser = $this->createKaurLabUser();

        $dosenPengajar = Dosen::factory()->create([
            'id_kelompok_keahlian' => $kelompokKeahlianKetuaKK->id,
            'id_jabatan_struktural' => JabatanStruktural::first()->id, // Dosen memiliki jabatan struktural
        ]);
        $dosenKoordinator = Dosen::factory()->create(['id_kelompok_keahlian' => KelompokKeahlian::first()->id]);

        $tahunAjaran = TahunAjaran::factory()->create(['tahun_ajaran' => '2026/2027', 'semester' => 'ganjil']);
        $programStudi = ProgramStudi::factory()->create(['nama' => 'S1 Teknik Informatika']);

        $picMatchingKK = Pic::firstOrCreate(['name' => $kelompokKeahlianKetuaKK->nama]);
        $matakuliah = Matakuliah::factory()->create(['id_pic' => $picMatchingKK->id, 'sks' => 6]); // MK 6 SKS

        $mappingKelas = MappingKelasMatakuliah::factory()->create([
            'id_matakuliah' => $matakuliah->id,
            'id_tahun_ajaran' => $tahunAjaran->id,
            'id_program_studi' => $programStudi->id,
            'nama_kelas' => 'TeamJabatanC',
            'team_teaching' => true, // Team Teaching
            'kuota' => 50,
        ]);

        KoordinatorMatakuliah::factory()->create([
            'id_dosen' => $dosenKoordinator->id,
            'id_mapping_kelas_matakuliah' => $mappingKelas->id,
        ]);

        $bebanSksPlotting = 3; // Beban SKS untuk team teaching

        // 2. Aksi 1: Ketua KK melakukan plotting
        $ketuaKKPlotResponse = $this->actingAs($ketuaKKUser, 'sanctum')->postJson('/api/v1/plottingan-pengajaran/start-plottingan-pengajaran', [
            'id_dosen' => $dosenPengajar->id,
            'id_mapping_kelas_matakuliah' => $mappingKelas->id,
            'beban_sks' => $bebanSksPlotting,
            'id_program_studi' => $programStudi->id,
        ]);

        // Assert Ketua KK plotting berhasil
        $ketuaKKPlotResponse->assertStatus(201)
                            ->assertJson(['success' => true, 'message' => 'Plottingan pengajaran berhasil disimpan.']);
        $this->assertDatabaseHas('plottingan_pengajarans', [
            'id_dosen' => $dosenPengajar->id,
            'id_mapping_kelas_matakuliah' => $mappingKelas->id,
            'beban_sks' => $bebanSksPlotting,
        ]);

        // 3. Aksi 2: Kaur Lab melihat hasil plottingan
        $laaViewResponse = $this->actingAs($kaurLabUser, 'sanctum')->getJson(
            '/api/v1/plottingan-pengajaran/tahun-ajaran/' . $tahunAjaran->id . '/program-studi/' . $programStudi->id
        );

        // 4. Assert LAA view berhasil dan data sesuai
        $laaViewResponse->assertStatus(200)
                        ->assertJson([
                            'success' => true,
                            'message' => 'Hasil Plottingan Pengajaran untuk Program Studi "' . $programStudi->nama . '" pada Tahun Ajaran "' . $tahunAjaran->tahun_ajaran . ' - ' . $tahunAjaran->semester . '" berhasil dimuat.',
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
     * Test Integrasi: Ketua KK memplot dosen (Team Teaching, tanpa Jabatan Struktural) dan Kaur Lab melihat hasilnya.
     *
     * @return void
     */
    public function test_ketua_kk_plots_team_no_jabatan_and_kaur_lab_views_results(): void
    {
        // 1. Persiapan Data
        $kelompokKeahlianKetuaKK = KelompokKeahlian::factory()->create(['nama' => 'KK Tanpa Jabatan Team']);
        $ketuaKKUser = $this->createKetuaKKUser($kelompokKeahlianKetuaKK);
        $kaurLabUser = $this->createKaurLabUser();

        $dosenPengajar = Dosen::factory()->create([
            'id_kelompok_keahlian' => $kelompokKeahlianKetuaKK->id,
            'id_jabatan_struktural' => null, // Tanpa Jabatan Struktural
        ]);
        $dosenKoordinator = Dosen::factory()->create(['id_kelompok_keahlian' => KelompokKeahlian::first()->id]);

        $tahunAjaran = TahunAjaran::factory()->create(['tahun_ajaran' => '2027/2028', 'semester' => 'ganjil']);
        $programStudi = ProgramStudi::factory()->create(['nama' => 'S1 Teknik Elektro']);

        $picMatchingKK = Pic::firstOrCreate(['name' => $kelompokKeahlianKetuaKK->nama]);
        $matakuliah = Matakuliah::factory()->create(['id_pic' => $picMatchingKK->id, 'sks' => 5]); // MK 5 SKS

        $mappingKelas = MappingKelasMatakuliah::factory()->create([
            'id_matakuliah' => $matakuliah->id,
            'id_tahun_ajaran' => $tahunAjaran->id,
            'id_program_studi' => $programStudi->id,
            'nama_kelas' => 'TeamNoJabatanD',
            'team_teaching' => true, // Team Teaching
            'kuota' => 50,
        ]);

        KoordinatorMatakuliah::factory()->create([
            'id_dosen' => $dosenKoordinator->id,
            'id_mapping_kelas_matakuliah' => $mappingKelas->id,
        ]);

        $bebanSksPlotting = 2; // Beban SKS untuk team teaching

        // 2. Aksi 1: Ketua KK melakukan plotting
        $ketuaKKPlotResponse = $this->actingAs($ketuaKKUser, 'sanctum')->postJson('/api/v1/plottingan-pengajaran/start-plottingan-pengajaran', [
            'id_dosen' => $dosenPengajar->id,
            'id_mapping_kelas_matakuliah' => $mappingKelas->id,
            'beban_sks' => $bebanSksPlotting,
            'id_program_studi' => $programStudi->id,
        ]);

        // Assert Ketua KK plotting berhasil
        $ketuaKKPlotResponse->assertStatus(201)
                            ->assertJson(['success' => true, 'message' => 'Plottingan pengajaran berhasil disimpan.']);
        $this->assertDatabaseHas('plottingan_pengajarans', [
            'id_dosen' => $dosenPengajar->id,
            'id_mapping_kelas_matakuliah' => $mappingKelas->id,
            'beban_sks' => $bebanSksPlotting,
        ]);

        // 3. Aksi 2: Kaur Lab melihat hasil plottingan
        $laaViewResponse = $this->actingAs($kaurLabUser, 'sanctum')->getJson(
            '/api/v1/plottingan-pengajaran/tahun-ajaran/' . $tahunAjaran->id . '/program-studi/' . $programStudi->id
        );

        // 4. Assert LAA view berhasil dan data sesuai
        $laaViewResponse->assertStatus(200)
                        ->assertJson([
                            'success' => true,
                            'message' => 'Hasil Plottingan Pengajaran untuk Program Studi "' . $programStudi->nama . '" pada Tahun Ajaran "' . $tahunAjaran->tahun_ajaran . ' - ' . $tahunAjaran->semester . '" berhasil dimuat.',
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
