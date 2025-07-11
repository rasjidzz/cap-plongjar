<?php

namespace Tests\Feature\Kaprodi;

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
use Illuminate\Support\Facades\Log;

class PlottinganPengajaranTeamTest extends TestCase
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
        Role::firstOrCreate(['id' => 4, 'name' => 'LayananAkademik']);

        // Buat data dasar untuk Foreign Key Factories
        for ($i = 1; $i <= 10; $i++) {
            KelompokKeahlian::firstOrCreate(['id' => $i, 'nama' => 'KK ' . chr(64 + $i)]);
            JabatanStruktural::firstOrCreate(['id' => $i, 'nama' => 'Jabatan ' . $i, 'konversi_sks' => $i]);
        }
        Pic::firstOrCreate(['id' => 1, 'name' => 'Default PIC']);
        ProgramStudi::firstOrCreate(['id' => 1, 'nama' => 'S1 Informatika']);
        TahunAjaran::firstOrCreate(['id' => 1, 'tahun_ajaran' => '2024/2025', 'semester' => 'ganjil']);
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
     * Test Case: Memastikan Kaprodi dapat menyimpan plottingan pengajaran dengan sukses (mode team teaching, dosen punya jabatan).
     * HTTP Response status code = 201 (Created)
     *
     * @return void
     */
    public function test_kaprodi_can_store_plotting_successfully_team_teaching_with_jabatan(): void
    {
        // 1. Persiapan Data
        $programStudi = ProgramStudi::firstOrCreate(['id' => 1, 'nama' => 'S1 Informatika']);
        $kaprodiUser = $this->createProgramStudiUser($programStudi);

        $konversiSksJabatan = 2; // SKS dari jabatan struktural
        $jabatanStruktural = JabatanStruktural::firstOrCreate(['id' => 1, 'nama' => 'Ketua Prodi', 'konversi_sks' => $konversiSksJabatan]);

        $dosen = Dosen::factory()->create([
            'id_kelompok_keahlian' => KelompokKeahlian::firstOrCreate(['id' => 1, 'nama' => 'KK A'])->id,
            'id_jabatan_struktural' => $jabatanStruktural->id, // Dosen memiliki jabatan struktural
        ]);

        $pic = Pic::firstOrCreate(['name' => $programStudi->nama]);
        $matakuliah = Matakuliah::factory()->create(['id_pic' => $pic->id, 'sks' => 6]); // Mata kuliah 6 SKS
        $tahunAjaran = TahunAjaran::firstOrCreate(['id' => 1, 'tahun_ajaran' => '2024/2025', 'semester' => 'ganjil']);

        // Buat Mapping Kelas Mata Kuliah sebagai TEAM TEACHING
        $mapping = MappingKelasMatakuliah::factory()->create([
            'id_matakuliah' => $matakuliah->id,
            'id_tahun_ajaran' => $tahunAjaran->id,
            'id_program_studi' => $programStudi->id,
            'nama_kelas' => 'Team Class',
            'team_teaching' => true, // Ini kuncinya: mode team teaching
            'kuota' => 50,
        ]);

        $inputBebanSks = 3; // Beban SKS yang diinput untuk dosen ini (bagian dari 6 SKS MK)

        // 2. Aksi: Login sebagai Kaprodi dan kirim POST request untuk menyimpan plottingan
        $response = $this->actingAs($kaprodiUser, 'sanctum')->postJson('/api/v1/plottingan-pengajaran/start-plottingan-pengajaran', [
            'id_dosen' => $dosen->id,
            'id_mapping_kelas_matakuliah' => $mapping->id,
            'beban_sks' => $inputBebanSks, // Beban SKS spesifik untuk team teaching
            'id_program_studi' => $programStudi->id,
        ]);

        // 3. Assertions
        $response->assertStatus(201) // Memastikan status HTTP 201 (Created)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Plottingan pengajaran berhasil disimpan.',
                     'data' => [
                         'id_dosen' => $dosen->id,
                         'id_mapping_kelas_matakuliah' => $mapping->id,
                         'beban_sks' => $inputBebanSks,
                     ]
                 ]);

        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'id_dosen',
                'id_mapping_kelas_matakuliah',
                'beban_sks',
                'created_at',
                'updated_at',
                'dosen' => ['id', 'name'],
                'mapping_kelas_matakuliah' => [
                    'id', 'nama_kelas', 'id_matakuliah',
                    'matakuliah' => ['id', 'nama_matakuliah', 'kode_matkul', 'sks']
                ]
            ]
        ]);

        // Verifikasi database: memastikan plottingan tersimpan
        $this->assertDatabaseHas('plottingan_pengajarans', [
            'id_dosen' => $dosen->id,
            'id_mapping_kelas_matakuliah' => $mapping->id,
            'beban_sks' => $inputBebanSks,
        ]);
    }

     public function test_kaprodi_can_store_plotting_successfully_team_teaching_no_jabatan(): void
    {
        // 1. Persiapan Data
        $programStudi = ProgramStudi::firstOrCreate(['id' => 1, 'nama' => 'S1 Informatika']);
        $kaprodiUser = $this->createProgramStudiUser($programStudi);

        $dosen = Dosen::factory()->create([
            'id_kelompok_keahlian' => KelompokKeahlian::firstOrCreate(['id' => 1, 'nama' => 'KK A'])->id,
            'id_jabatan_struktural' => null, // Dosen TIDAK memiliki jabatan struktural
        ]);

        $pic = Pic::firstOrCreate(['name' => $programStudi->nama]);
        $matakuliah = Matakuliah::factory()->create(['id_pic' => $pic->id, 'sks' => 6]); // Mata kuliah 6 SKS
        $tahunAjaran = TahunAjaran::firstOrCreate(['id' => 1, 'tahun_ajaran' => '2024/2025', 'semester' => 'ganjil']);

        // Buat Mapping Kelas Mata Kuliah sebagai TEAM TEACHING
        $mapping = MappingKelasMatakuliah::factory()->create([
            'id_matakuliah' => $matakuliah->id,
            'id_tahun_ajaran' => $tahunAjaran->id,
            'id_program_studi' => $programStudi->id,
            'nama_kelas' => 'Team Class No Jabatan',
            'team_teaching' => true, // Ini kuncinya: mode team teaching
            'kuota' => 50,
        ]);

        $inputBebanSks = 4; // Beban SKS yang diinput untuk dosen ini (bagian dari 6 SKS MK)

        // 2. Aksi: Login sebagai Kaprodi dan kirim POST request untuk menyimpan plottingan
        $response = $this->actingAs($kaprodiUser, 'sanctum')->postJson('/api/v1/plottingan-pengajaran/start-plottingan-pengajaran', [
            'id_dosen' => $dosen->id,
            'id_mapping_kelas_matakuliah' => $mapping->id,
            'beban_sks' => $inputBebanSks, // Beban SKS spesifik untuk team teaching
            'id_program_studi' => $programStudi->id,
        ]);

        // 3. Assertions
        $response->assertStatus(201) // Memastikan status HTTP 201 (Created)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Plottingan pengajaran berhasil disimpan.',
                     'data' => [
                         'id_dosen' => $dosen->id,
                         'id_mapping_kelas_matakuliah' => $mapping->id,
                         'beban_sks' => $inputBebanSks,
                     ]
                 ]);

        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'id_dosen',
                'id_mapping_kelas_matakuliah',
                'beban_sks',
                'created_at',
                'updated_at',
                'dosen' => ['id', 'name'],
                'mapping_kelas_matakuliah' => [
                    'id', 'nama_kelas', 'id_matakuliah',
                    'matakuliah' => ['id', 'nama_matakuliah', 'kode_matkul', 'sks']
                ]
            ]
        ]);

        // Verifikasi database: memastikan plottingan tersimpan
        $this->assertDatabaseHas('plottingan_pengajarans', [
            'id_dosen' => $dosen->id,
            'id_mapping_kelas_matakuliah' => $mapping->id,
            'beban_sks' => $inputBebanSks,
        ]);
    }

    public function test_kaprodi_cannot_store_plotting_team_teaching_missing_beban_sks(): void
    {
        // 1. Persiapan Data
        $programStudi = ProgramStudi::firstOrCreate(['id' => 1, 'nama' => 'S1 Informatika']);
        $kaprodiUser = $this->createProgramStudiUser($programStudi);

        $dosen = Dosen::factory()->create([
            'id_kelompok_keahlian' => KelompokKeahlian::firstOrCreate(['id' => 1, 'nama' => 'KK A'])->id,
            'id_jabatan_struktural' => JabatanStruktural::firstOrCreate(['id' => 1, 'nama' => 'Jabatan A', 'konversi_sks' => 1])->id,
        ]);

        $pic = Pic::firstOrCreate(['name' => $programStudi->nama]);
        $matakuliah = Matakuliah::factory()->create(['id_pic' => $pic->id, 'sks' => 6]); // Mata kuliah 6 SKS
        $tahunAjaran = TahunAjaran::firstOrCreate(['id' => 1, 'tahun_ajaran' => '2024/2025', 'semester' => 'ganjil']);

        // Buat Mapping Kelas Mata Kuliah sebagai TEAM TEACHING
        $mapping = MappingKelasMatakuliah::factory()->create([
            'id_matakuliah' => $matakuliah->id,
            'id_tahun_ajaran' => $tahunAjaran->id,
            'id_program_studi' => $programStudi->id,
            'nama_kelas' => 'Team Class Missing SKS',
            'team_teaching' => true, // Ini kuncinya: mode team teaching
            'kuota' => 50,
        ]);

        // 2. Aksi: Login sebagai Kaprodi dan kirim POST request TANPA 'beban_sks'
        $response = $this->actingAs($kaprodiUser, 'sanctum')->postJson('/api/v1/plottingan-pengajaran/start-plottingan-pengajaran', [
            'id_dosen' => $dosen->id,
            'id_mapping_kelas_matakuliah' => $mapping->id,
            // 'beban_sks' tidak disertakan
            'id_program_studi' => $programStudi->id,
        ]);

        // 3. Assertions
        $response->assertStatus(422) // Memastikan status HTTP 422
                 ->assertJson([
                     'success' => false,
                     'message' => 'Beban SKS wajib diisi untuk mode team teaching.',
                     'errors' => [
                         'beban_sks' => ['Beban SKS wajib diisi untuk team teaching.']
                     ]
                 ]);

        // Pastikan tidak ada plottingan yang dibuat
        $this->assertDatabaseMissing('plottingan_pengajarans', [
            'id_dosen' => $dosen->id,
            'id_mapping_kelas_matakuliah' => $mapping->id,
        ]);
    }

    public function test_kaprodi_cannot_store_plotting_team_teaching_beban_sks_exceeds_matakuliah_sks(): void
    {
        // 1. Persiapan Data
        $programStudi = ProgramStudi::firstOrCreate(['id' => 1, 'nama' => 'S1 Informatika']);
        $kaprodiUser = $this->createProgramStudiUser($programStudi);

        $dosen = Dosen::factory()->create([
            'id_kelompok_keahlian' => KelompokKeahlian::firstOrCreate(['id' => 1, 'nama' => 'KK A'])->id,
            'id_jabatan_struktural' => JabatanStruktural::firstOrCreate(['id' => 1, 'nama' => 'Jabatan A', 'konversi_sks' => 1])->id,
        ]);

        $pic = Pic::firstOrCreate(['name' => $programStudi->nama]);
        $sksMatakuliah = 3; // Mata kuliah 3 SKS
        $matakuliah = Matakuliah::factory()->create(['id_pic' => $pic->id, 'sks' => $sksMatakuliah]);
        $tahunAjaran = TahunAjaran::firstOrCreate(['id' => 1, 'tahun_ajaran' => '2024/2025', 'semester' => 'ganjil']);

        // Buat Mapping Kelas Mata Kuliah sebagai TEAM TEACHING
        $mapping = MappingKelasMatakuliah::factory()->create([
            'id_matakuliah' => $matakuliah->id,
            'id_tahun_ajaran' => $tahunAjaran->id,
            'id_program_studi' => $programStudi->id,
            'nama_kelas' => 'Team Class Exceed SKS',
            'team_teaching' => true,
            'kuota' => 50,
        ]);

        $inputBebanSks = $sksMatakuliah + 1; // Beban SKS yang diinput melebihi SKS mata kuliah (misal 4 SKS)

        // 2. Aksi: Login sebagai Kaprodi dan kirim POST request
        $response = $this->actingAs($kaprodiUser, 'sanctum')->postJson('/api/v1/plottingan-pengajaran/start-plottingan-pengajaran', [
            'id_dosen' => $dosen->id,
            'id_mapping_kelas_matakuliah' => $mapping->id,
            'beban_sks' => $inputBebanSks,
            'id_program_studi' => $programStudi->id,
        ]);

        // 3. Assertions
        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Beban SKS yang diinput (' . $inputBebanSks . ') untuk dosen ini tidak boleh melebihi total SKS mata kuliah (' . $sksMatakuliah . ').',
                     'errors' => [
                         'beban_sks' => ['Beban SKS input melebihi SKS mata kuliah.']
                     ]
                 ]);

        // Pastikan tidak ada plottingan yang dibuat
        $this->assertDatabaseMissing('plottingan_pengajarans', [
            'id_dosen' => $dosen->id,
            'id_mapping_kelas_matakuliah' => $mapping->id,
        ]);
    }

    public function test_kaprodi_cannot_store_plotting_team_teaching_total_sks_exceeds_class_sks(): void
    {
        // 1. Persiapan Data
        $programStudi = ProgramStudi::firstOrCreate(['id' => 1, 'nama' => 'S1 Informatika']);
        $kaprodiUser = $this->createProgramStudiUser($programStudi);

        $dosenExisting = Dosen::factory()->create([
            'id_kelompok_keahlian' => KelompokKeahlian::firstOrCreate(['id' => 1, 'nama' => 'KK A'])->id,
            'id_jabatan_struktural' => JabatanStruktural::firstOrCreate(['id' => 1, 'nama' => 'Jabatan A', 'konversi_sks' => 1])->id,
        ]);
        $dosenNew = Dosen::factory()->create([
            'id_kelompok_keahlian' => KelompokKeahlian::firstOrCreate(['id' => 2, 'nama' => 'KK B'])->id,
            'id_jabatan_struktural' => JabatanStruktural::firstOrCreate(['id' => 2, 'nama' => 'Jabatan B', 'konversi_sks' => 2])->id,
        ]);

        $pic = Pic::firstOrCreate(['name' => $programStudi->nama]);
        $sksMatakuliah = 6; // Mata kuliah 6 SKS
        $matakuliah = Matakuliah::factory()->create(['id_pic' => $pic->id, 'sks' => $sksMatakuliah]);
        $tahunAjaran = TahunAjaran::firstOrCreate(['id' => 1, 'tahun_ajaran' => '2024/2025', 'semester' => 'ganjil']);

        // Buat Mapping Kelas Mata Kuliah sebagai TEAM TEACHING
        $mappingTeam = MappingKelasMatakuliah::factory()->create([
            'id_matakuliah' => $matakuliah->id,
            'id_tahun_ajaran' => $tahunAjaran->id,
            'id_program_studi' => $programStudi->id,
            'nama_kelas' => 'Team Class Total Exceed',
            'team_teaching' => true,
            'kuota' => 50,
        ]);

        // Plottingan yang sudah ada untuk kelas team teaching ini
        $bebanSksDosenLain = 4; // Dosen lain sudah memplot 4 SKS
        PlottinganPengajaran::create([
            'id_dosen' => $dosenExisting->id,
            'id_mapping_kelas_matakuliah' => $mappingTeam->id,
            'beban_sks' => $bebanSksDosenLain,
        ]);

        $inputBebanSksNew = 3; // Beban SKS yang diinput dosen baru (3 SKS)
        // Total SKS kelas setelah plottingan baru: 4 (sebelumnya) + 3 (baru) = 7 SKS
        // Ini melebihi SKS mata kuliah (6 SKS)

        // 2. Aksi: Login sebagai Kaprodi dan coba plot yang akan melebihi total SKS kelas
        $response = $this->actingAs($kaprodiUser, 'sanctum')->postJson('/api/v1/plottingan-pengajaran/start-plottingan-pengajaran', [
            'id_dosen' => $dosenNew->id,
            'id_mapping_kelas_matakuliah' => $mappingTeam->id,
            'beban_sks' => $inputBebanSksNew,
            'id_program_studi' => $programStudi->id,
        ]);

        // 3. Assertions
        $potensiTotalSksUntukKelasIni = $bebanSksDosenLain + $inputBebanSksNew; // 4 + 3 = 7
        $messageExpected = 'Total beban SKS untuk kelas ini (' . $potensiTotalSksUntukKelasIni . ') akan melebihi SKS mata kuliah (' . $sksMatakuliah . '). SKS sudah terplot oleh dosen lain: ' . $bebanSksDosenLain . '.';

        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                     'message' => $messageExpected,
                     'errors' => [
                         'beban_sks' => ['Penambahan SKS ini akan membuat total SKS kelas melebihi batas SKS mata kuliah.']
                     ]
                 ]);

        // Pastikan plottingan baru tidak dibuat
        $this->assertDatabaseMissing('plottingan_pengajarans', [
            'id_dosen' => $dosenNew->id,
            'id_mapping_kelas_matakuliah' => $mappingTeam->id,
        ]);
        // Pastikan plottingan lama tetap ada
        $this->assertDatabaseHas('plottingan_pengajarans', [
            'id_dosen' => $dosenExisting->id,
            'id_mapping_kelas_matakuliah' => $mappingTeam->id,
        ]);
    }

    public function test_kaprodi_cannot_store_plotting_total_sks_exceeds_max_with_jabatan_team_teaching(): void
    {
        // 1. Persiapan Data
        $programStudi = ProgramStudi::firstOrCreate(['id' => 1, 'nama' => 'S1 Informatika']);
        $kaprodiUser = $this->createProgramStudiUser($programStudi);

        $konversiSksJabatan = 5; // SKS dari jabatan struktural
        $jabatanStruktural = JabatanStruktural::firstOrCreate(['id' => 1, 'nama' => 'Ketua Jurusan', 'konversi_sks' => $konversiSksJabatan]);

        $dosen = Dosen::factory()->create([
            'id_kelompok_keahlian' => KelompokKeahlian::firstOrCreate(['id' => 1, 'nama' => 'KK A'])->id,
            'id_jabatan_struktural' => $jabatanStruktural->id, // Dosen memiliki jabatan struktural
        ]);

        $pic = Pic::firstOrCreate(['name' => $programStudi->nama]);
        $sksMatakuliah = 6; // Mata kuliah 6 SKS (untuk plotting baru)
        $matakuliahNew = Matakuliah::factory()->create(['id_pic' => $pic->id, 'sks' => $sksMatakuliah]);
        $tahunAjaran = TahunAjaran::firstOrCreate(['id' => 1, 'tahun_ajaran' => '2024/2025', 'semester' => 'ganjil']);

        // Buat plottingan sebelumnya untuk dosen ini agar total SKS-nya tinggi, tapi masih di bawah batas
        $existingSksDosen = 8; // Dosen sudah mengajar 8 SKS
        $matakuliahExisting = Matakuliah::factory()->create(['id_pic' => $pic->id, 'sks' => $existingSksDosen]);
        $mappingExisting = MappingKelasMatakuliah::factory()->create([
            'id_matakuliah' => $matakuliahExisting->id,
            'id_tahun_ajaran' => $tahunAjaran->id,
            'id_program_studi' => $programStudi->id,
            'team_teaching' => false, // Bisa solo atau team teaching, yang penting beban SKS-nya
        ]);
        PlottinganPengajaran::create([
            'id_dosen' => $dosen->id,
            'id_mapping_kelas_matakuliah' => $mappingExisting->id,
            'beban_sks' => $existingSksDosen,
        ]);

        // Total SKS proyeksi sebelum plotting baru: 5 (jabatan) + 8 (mengajar sebelumnya) = 13 SKS
        // Maksimal SKS Dosen = 16 SKS
        // Plottingan baru: 6 SKS (dari matakuliahNew)
        // Proyeksi akhir: 13 + 6 = 19 SKS (melebihi batas 16 SKS)

        $mappingNew = MappingKelasMatakuliah::factory()->create([
            'id_matakuliah' => $matakuliahNew->id, // Mata kuliah 6 SKS
            'id_tahun_ajaran' => $tahunAjaran->id,
            'id_program_studi' => $programStudi->id,
            'nama_kelas' => 'Kelas Exceed Total SKS',
            'team_teaching' => true, // Team teaching
            'kuota' => 50,
        ]);

        $inputBebanSksNew = $matakuliahNew->sks; // Beban SKS penuh dari MK baru (6 SKS)

        // 2. Aksi: Login sebagai Kaprodi dan coba plot yang akan melebihi batas SKS dosen
        $response = $this->actingAs($kaprodiUser, 'sanctum')->postJson('/api/v1/plottingan-pengajaran/start-plottingan-pengajaran', [
            'id_dosen' => $dosen->id,
            'id_mapping_kelas_matakuliah' => $mappingNew->id,
            'beban_sks' => $inputBebanSksNew,
            'id_program_studi' => $programStudi->id,
        ]);

        // 3. Assertions
        $maksimalSksDosen = 16;
        $totalSksMengajarDosen = $existingSksDosen;
        $inputBebanSks = $inputBebanSksNew;
        $totalSksProyeksi = $konversiSksJabatan + $totalSksMengajarDosen + $inputBebanSks; // 5 + 8 + 6 = 19

        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Plottingan gagal: Total SKS dosen akan melebihi batas maksimal (' . $maksimalSksDosen . ' SKS). Proyeksi: ' . $totalSksProyeksi . ' SKS (Jabatan: ' . $konversiSksJabatan . ' SKS + Mengajar Sebelumnya: ' . $totalSksMengajarDosen . ' SKS + Plottingan Ini: ' . $inputBebanSks . ' SKS).',
                     'errors' => [
                         'id_dosen' => ['Total SKS dosen akan melebihi batas maksimal.']
                     ]
                 ]);

        // Pastikan plottingan baru tidak dibuat
        $this->assertDatabaseMissing('plottingan_pengajarans', [
            'id_dosen' => $dosen->id,
            'id_mapping_kelas_matakuliah' => $mappingNew->id,
        ]);
    }

    public function test_kaprodi_cannot_store_plotting_total_sks_exceeds_max_no_jabatan_team_teaching(): void
    {
        // 1. Persiapan Data
        $programStudi = ProgramStudi::firstOrCreate(['id' => 1, 'nama' => 'S1 Informatika']);
        $kaprodiUser = $this->createProgramStudiUser($programStudi);

        $konversiSksJabatan = 0; // Tidak ada SKS dari jabatan struktural
        $dosen = Dosen::factory()->create([
            'id_kelompok_keahlian' => KelompokKeahlian::firstOrCreate(['id' => 1, 'nama' => 'KK A'])->id,
            'id_jabatan_struktural' => null, // Dosen TIDAK memiliki jabatan struktural
        ]);

        $pic = Pic::firstOrCreate(['name' => $programStudi->nama]);
        $sksMatakuliah = 4; // Mata kuliah 4 SKS
        $matakuliah = Matakuliah::factory()->create(['id_pic' => $pic->id, 'sks' => $sksMatakuliah]);
        $tahunAjaran = TahunAjaran::firstOrCreate(['id' => 1, 'tahun_ajaran' => '2024/2025', 'semester' => 'ganjil']);

        // Buat plottingan sebelumnya untuk dosen ini agar total SKS-nya tinggi
        $existingSksDosen = 13; // Dosen sudah mengajar 13 SKS
        $matakuliahExisting = Matakuliah::factory()->create(['id_pic' => $pic->id, 'sks' => $existingSksDosen]);
        $mappingExisting = MappingKelasMatakuliah::factory()->create([
            'id_matakuliah' => $matakuliahExisting->id,
            'id_tahun_ajaran' => $tahunAjaran->id,
            'id_program_studi' => $programStudi->id,
            'team_teaching' => false,
        ]);
        PlottinganPengajaran::create([
            'id_dosen' => $dosen->id,
            'id_mapping_kelas_matakuliah' => $mappingExisting->id,
            'beban_sks' => $existingSksDosen,
        ]);

        // Total SKS proyeksi: 0 (jabatan) + 13 (mengajar sebelumnya) = 13 SKS
        // Maksimal SKS Dosen = 16 SKS
        // Plottingan baru: 4 SKS
        // Proyeksi akhir: 13 + 4 = 17 SKS (melebihi batas 16 SKS)

        $mappingNew = MappingKelasMatakuliah::factory()->create([
            'id_matakuliah' => $matakuliah->id, // Mata kuliah 4 SKS
            'id_tahun_ajaran' => $tahunAjaran->id,
            'id_program_studi' => $programStudi->id,
            'nama_kelas' => 'Kelas Y',
            'team_teaching' => true, // Team teaching
            'kuota' => 50,
        ]);

        $inputBebanSksNew = $matakuliah->sks; // Beban SKS dari MK baru (4 SKS)

        // 2. Aksi: Login sebagai Kaprodi dan coba plot yang akan melebihi batas SKS
        $response = $this->actingAs($kaprodiUser, 'sanctum')->postJson('/api/v1/plottingan-pengajaran/start-plottingan-pengajaran', [
            'id_dosen' => $dosen->id,
            'id_mapping_kelas_matakuliah' => $mappingNew->id,
            'beban_sks' => $inputBebanSksNew,
            'id_program_studi' => $programStudi->id,
        ]);

        // 3. Assertions
        $maksimalSksDosen = 16;
        $totalSksMengajarDosen = $existingSksDosen;
        $inputBebanSks = $inputBebanSksNew;
        $totalSksProyeksi = $konversiSksJabatan + $totalSksMengajarDosen + $inputBebanSks; // 0 + 13 + 4 = 17

        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Plottingan gagal: Total SKS dosen akan melebihi batas maksimal (' . $maksimalSksDosen . ' SKS). Proyeksi: ' . $totalSksProyeksi . ' SKS (Jabatan: ' . $konversiSksJabatan . ' SKS + Mengajar Sebelumnya: ' . $totalSksMengajarDosen . ' SKS + Plottingan Ini: ' . $inputBebanSks . ' SKS).',
                     'errors' => [
                         'id_dosen' => ['Total SKS dosen akan melebihi batas maksimal.']
                     ]
                 ]);

        // Pastikan plottingan baru tidak dibuat
        $this->assertDatabaseMissing('plottingan_pengajarans', [
            'id_dosen' => $dosen->id,
            'id_mapping_kelas_matakuliah' => $mappingNew->id,
        ]);
    }


}

