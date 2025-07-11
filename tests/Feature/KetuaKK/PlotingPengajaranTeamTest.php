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
use App\Models\KelompokKeahlian;
use App\Models\JabatanStruktural;
use App\Models\Pic;
use Illuminate\Support\Facades\Log;

class PlotingPengajaranTeamTest extends TestCase
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

        for ($i = 1; $i <= 10; $i++) {
            KelompokKeahlian::firstOrCreate(['id' => $i, 'nama' => 'KK ' . chr(64 + $i)]);
            JabatanStruktural::firstOrCreate(['id' => $i, 'nama' => 'Jabatan ' . $i, 'konversi_sks' => $i]);
        }
        // Pastikan ada PIC default, tapi untuk test tertentu kita akan buat PIC yang namanya match dengan KK
        Pic::firstOrCreate(['id' => 1, 'name' => 'Default PIC']);
        ProgramStudi::firstOrCreate(['id' => 1, 'nama' => 'S1 Informatika']); // Perlu ProgramStudi untuk DosenFactory
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

     public function test_ketua_kk_can_store_plotting_successfully_team_teaching_with_jabatan(): void
    {
        // 1. Persiapan Data
        $kelompokKeahlian = KelompokKeahlian::firstOrCreate(['id' => 1, 'nama' => 'KK A']); // Kelompok Keahlian untuk Ketua KK
        $ketuaKKUser = $this->createKetuaKKUser($kelompokKeahlian);
        $programStudi = ProgramStudi::firstOrCreate(['id' => 1, 'nama' => 'S1 Informatika']);

        $konversiSksJabatan = 2; // SKS dari jabatan struktural
        $jabatanStruktural = JabatanStruktural::firstOrCreate(['id' => 1, 'nama' => 'Ketua Prodi', 'konversi_sks' => $konversiSksJabatan]);

        $dosen = Dosen::factory()->create([
            'id_kelompok_keahlian' => KelompokKeahlian::firstOrCreate(['id' => 1, 'nama' => 'KK A'])->id,
            'id_jabatan_struktural' => $jabatanStruktural->id, // Dosen memiliki jabatan struktural
        ]);

        $pic = Pic::firstOrCreate(['name' => $kelompokKeahlian->nama]);
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

        $inputBebanSks = 3;

        // 2. Aksi: Login sebagai Kaprodi dan kirim POST request untuk menyimpan plottingan
        $response = $this->actingAs($ketuaKKUser, 'sanctum')->postJson('/api/v1/plottingan-pengajaran/start-plottingan-pengajaran', [
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

    public function test_ketua_kk_can_store_plotting_successfully_team_teaching_with_no_jabatan(): void
    {
        // 1. Persiapan Data
        $kelompokKeahlian = KelompokKeahlian::firstOrCreate(['id' => 1, 'nama' => 'KK A']); // Kelompok Keahlian untuk Ketua KK
        $ketuaKKUser = $this->createKetuaKKUser($kelompokKeahlian);
        $programStudi = ProgramStudi::firstOrCreate(['id' => 1, 'nama' => 'S1 Informatika']);

        $dosen = Dosen::factory()->create([
            'id_kelompok_keahlian' => KelompokKeahlian::firstOrCreate(['id' => 1, 'nama' => 'KK A'])->id,
            'id_jabatan_struktural' => null,
        ]);

        $pic = Pic::firstOrCreate(['name' => $kelompokKeahlian->nama]);
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

        $inputBebanSks = 3;

        // 2. Aksi: Login sebagai Kaprodi dan kirim POST request untuk menyimpan plottingan
        $response = $this->actingAs($ketuaKKUser, 'sanctum')->postJson('/api/v1/plottingan-pengajaran/start-plottingan-pengajaran', [
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

     public function test_ketua_kk_cannot_store_plotting_team_teaching_missing_beban_sks(): void
    {
        // 1. Persiapan Data
        $kelompokKeahlian = KelompokKeahlian::firstOrCreate(['id' => 1, 'nama' => 'KK A']); // Kelompok Keahlian untuk Ketua KK
        $ketuaKKUser = $this->createKetuaKKUser($kelompokKeahlian);
        $programStudi = ProgramStudi::firstOrCreate(['id' => 1, 'nama' => 'S1 Informatika']);

        $dosen = Dosen::factory()->create([
            'id_kelompok_keahlian' => KelompokKeahlian::firstOrCreate(['id' => 1, 'nama' => 'KK A'])->id,
            'id_jabatan_struktural' => null,
        ]);

        $pic = Pic::firstOrCreate(['name' => $kelompokKeahlian->nama]);
        $matakuliah = Matakuliah::factory()->create(['id_pic' => $pic->id, 'sks' => 6]); // Mata kuliah 6 SKS
        $tahunAjaran = TahunAjaran::firstOrCreate(['id' => 1, 'tahun_ajaran' => '2024/2025', 'semester' => 'ganjil']);

        // Buat Mapping Kelas Mata Kuliah sebagai TEAM TEACHING
        $mapping = MappingKelasMatakuliah::factory()->create([
            'id_matakuliah' => $matakuliah->id,
            'id_tahun_ajaran' => $tahunAjaran->id,
            'id_program_studi' => $programStudi->id,
            'nama_kelas' => 'Team Class No Jabatan',
            'team_teaching' => true,
            'kuota' => 50,
        ]);

        $inputBebanSks = 3;

        // 2. Aksi: Login sebagai Kaprodi dan kirim POST request untuk menyimpan plottingan
        $response = $this->actingAs($ketuaKKUser, 'sanctum')->postJson('/api/v1/plottingan-pengajaran/start-plottingan-pengajaran', [
            'id_dosen' => $dosen->id,
            'id_mapping_kelas_matakuliah' => $mapping->id,
            'id_program_studi' => $programStudi->id,
        ]);

        // 3. Assertions
         $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Beban SKS wajib diisi untuk mode team teaching.',
                     'errors' => [
                         'beban_sks' => ['Beban SKS wajib diisi untuk team teaching.']
                     ]
                 ]);

        // Verifikasi database: memastikan plottingan tersimpan
       $this->assertDatabaseMissing('plottingan_pengajarans', [
            'id_dosen' => $dosen->id,
            'id_mapping_kelas_matakuliah' => $mapping->id,
        ]);
    }

     public function test_ketua_kk_cannot_store_plotting_team_teaching_beban_sks_exceeds_matakuliah_sks(): void
    {
        // 1. Persiapan Data
        $kelompokKeahlian = KelompokKeahlian::firstOrCreate(['id' => 1, 'nama' => 'KK A']); // Kelompok Keahlian untuk Ketua KK
        $ketuaKKUser = $this->createKetuaKKUser($kelompokKeahlian);
        $programStudi = ProgramStudi::firstOrCreate(['id' => 1, 'nama' => 'S1 Informatika']);

        $dosen = Dosen::factory()->create([
            'id_kelompok_keahlian' => KelompokKeahlian::firstOrCreate(['id' => 1, 'nama' => 'KK A'])->id,
            'id_jabatan_struktural' => JabatanStruktural::firstOrCreate(['id' => 1, 'nama' => 'Jabatan A', 'konversi_sks' => 1])->id,
        ]);

        $pic = Pic::firstOrCreate(['name' => $kelompokKeahlian->nama]);
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

        $inputBebanSks = $sksMatakuliah + 1;

        // 2. Aksi: Login sebagai Kaprodi dan kirim POST request
        $response = $this->actingAs($ketuaKKUser, 'sanctum')->postJson('/api/v1/plottingan-pengajaran/start-plottingan-pengajaran', [
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

     public function test_ketua_kk_cannot_store_plotting_team_teaching_total_sks_exceeds_class_sks(): void
    {
        // 1. Persiapan Data
        $kelompokKeahlian = KelompokKeahlian::firstOrCreate(['id' => 1, 'nama' => 'KK A']); // Kelompok Keahlian untuk Ketua KK
        $ketuaKKUser = $this->createKetuaKKUser($kelompokKeahlian);
        $programStudi = ProgramStudi::firstOrCreate(['id' => 1, 'nama' => 'S1 Informatika']);

        $dosenExisting = Dosen::factory()->create([
            'id_kelompok_keahlian' => KelompokKeahlian::firstOrCreate(['id' => 1, 'nama' => 'KK A'])->id,
            'id_jabatan_struktural' => JabatanStruktural::firstOrCreate(['id' => 1, 'nama' => 'Jabatan A', 'konversi_sks' => 1])->id,
        ]);
        $dosenNew = Dosen::factory()->create([
            'id_kelompok_keahlian' => KelompokKeahlian::firstOrCreate(['id' => 2, 'nama' => 'KK B'])->id,
            'id_jabatan_struktural' => JabatanStruktural::firstOrCreate(['id' => 2, 'nama' => 'Jabatan B', 'konversi_sks' => 2])->id,
        ]);

        $pic = Pic::firstOrCreate(['name' => $kelompokKeahlian->nama]);
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

        $inputBebanSksNew = 3;

        // 2. Aksi: Login sebagai Kaprodi dan coba plot yang akan melebihi total SKS kelas
        $response = $this->actingAs($ketuaKKUser, 'sanctum')->postJson('/api/v1/plottingan-pengajaran/start-plottingan-pengajaran', [
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

    public function test_ketua_kk_cannot_store_plotting_total_sks_exceeds_max_with_jabatan_team_teaching(): void
    {
        // 1. Persiapan Data
        $kelompokKeahlian = KelompokKeahlian::firstOrCreate(['id' => 1, 'nama' => 'KK A']); // Kelompok Keahlian untuk Ketua KK
        $ketuaKKUser = $this->createKetuaKKUser($kelompokKeahlian);
        $programStudi = ProgramStudi::firstOrCreate(['id' => 1, 'nama' => 'S1 Informatika']);

        $konversiSksJabatan = 5; // SKS dari jabatan struktural
        $jabatanStruktural = JabatanStruktural::firstOrCreate(['id' => 1, 'nama' => 'Ketua Jurusan', 'konversi_sks' => $konversiSksJabatan]);

        $dosen = Dosen::factory()->create([
            'id_kelompok_keahlian' => KelompokKeahlian::firstOrCreate(['id' => 1, 'nama' => 'KK A'])->id,
            'id_jabatan_struktural' => $jabatanStruktural->id, // Dosen memiliki jabatan struktural
        ]);

        $pic = Pic::firstOrCreate(['name' => $kelompokKeahlian->nama]);
        $sksMatakuliah = 6;
        $matakuliahNew = Matakuliah::factory()->create(['id_pic' => $pic->id, 'sks' => $sksMatakuliah]);
        $tahunAjaran = TahunAjaran::firstOrCreate(['id' => 1, 'tahun_ajaran' => '2024/2025', 'semester' => 'ganjil']);

        // Buat plottingan sebelumnya untuk dosen ini agar total SKS-nya tinggi, tapi masih di bawah batas
        $existingSksDosen = 14; // Dosen sudah mengajar 8 SKS
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

        // Total SKS proyeksi sebelum plotting baru: 5 (jabatan) + 8 (mengajar sebelumnya) = 13 SKS
        // Maksimal SKS Dosen = 16 SKS
        // Plottingan baru: 6 SKS (dari matakuliahNew)
        // Proyeksi akhir: 13 + 6 = 19 SKS (melebihi batas 16 SKS)

        $mappingNew = MappingKelasMatakuliah::factory()->create([
            'id_matakuliah' => $matakuliahNew->id,
            'id_tahun_ajaran' => $tahunAjaran->id,
            'id_program_studi' => $programStudi->id,
            'nama_kelas' => 'Kelas Exceed Total SKS',
            'team_teaching' => true, // Team teaching
            'kuota' => 50,
        ]);

        $inputBebanSksNew = 5;

        // 2. Aksi: Login sebagai Kaprodi dan coba plot yang akan melebihi batas SKS dosen
        $response = $this->actingAs($ketuaKKUser, 'sanctum')->postJson('/api/v1/plottingan-pengajaran/start-plottingan-pengajaran', [
            'id_dosen' => $dosen->id,
            'id_mapping_kelas_matakuliah' => $mappingNew->id,
            'beban_sks' => $inputBebanSksNew,
            'id_program_studi' => $programStudi->id,
        ]);

        // 3. Assertions
        $maksimalSksDosen = 16;
        $totalSksMengajarDosen = $existingSksDosen;
        $inputBebanSks = $inputBebanSksNew;
        $totalSksProyeksi = $konversiSksJabatan + $totalSksMengajarDosen + $inputBebanSks;

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

     public function test_ketua_kk_cannot_store_plotting_total_sks_exceeds_max_no_jabatan_team_teaching(): void
    {
        // 1. Persiapan Data
        $kelompokKeahlian = KelompokKeahlian::firstOrCreate(['id' => 1, 'nama' => 'KK A']); // Kelompok Keahlian untuk Ketua KK
        $ketuaKKUser = $this->createKetuaKKUser($kelompokKeahlian);
        $programStudi = ProgramStudi::firstOrCreate(['id' => 1, 'nama' => 'S1 Informatika']);

        $konversiSksJabatan = 0;
        $dosen = Dosen::factory()->create([
            'id_kelompok_keahlian' => KelompokKeahlian::firstOrCreate(['id' => 1, 'nama' => 'KK A'])->id,
            'id_jabatan_struktural' => null,
        ]);

        $pic = Pic::firstOrCreate(['name' => $kelompokKeahlian->nama]);
        $sksMatakuliah = 4;
        $matakuliah = Matakuliah::factory()->create(['id_pic' => $pic->id, 'sks' => $sksMatakuliah]);
        $tahunAjaran = TahunAjaran::firstOrCreate(['id' => 1, 'tahun_ajaran' => '2024/2025', 'semester' => 'ganjil']);

        // Buat plottingan sebelumnya untuk dosen ini agar total SKS-nya tinggi
        $existingSksDosen = 14;
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


        $mappingNew = MappingKelasMatakuliah::factory()->create([
            'id_matakuliah' => $matakuliah->id, // Mata kuliah 4 SKS
            'id_tahun_ajaran' => $tahunAjaran->id,
            'id_program_studi' => $programStudi->id,
            'nama_kelas' => 'Kelas Y',
            'team_teaching' => true, // Team teaching
            'kuota' => 50,
        ]);

        $inputBebanSksNew = 3; // Beban SKS dari MK baru (4 SKS)

        // 2. Aksi: Login sebagai Kaprodi dan coba plot yang akan melebihi batas SKS
        $response = $this->actingAs($ketuaKKUser, 'sanctum')->postJson('/api/v1/plottingan-pengajaran/start-plottingan-pengajaran', [
            'id_dosen' => $dosen->id,
            'id_mapping_kelas_matakuliah' => $mappingNew->id,
            'beban_sks' => $inputBebanSksNew,
            'id_program_studi' => $programStudi->id,
        ]);

        // 3. Assertions
        $maksimalSksDosen = 16;
        $totalSksMengajarDosen = $existingSksDosen;
        $inputBebanSks = $inputBebanSksNew;
        $totalSksProyeksi = $konversiSksJabatan + $totalSksMengajarDosen + $inputBebanSks;

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
