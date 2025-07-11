<?php

namespace Tests\Feature\KetuaKK; // Namespace disesuaikan untuk path baru

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

class PlotingPengajaranSoloTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['id' => 1, 'name' => 'Superadmin']);
        Role::firstOrCreate(['id' => 2, 'name' => 'ProgramStudi']);
        Role::firstOrCreate(['id' => 3, 'name' => 'KelompokKeahlian']); // Role Ketua KK
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

    protected function createUnauthorizedPlottingUser()
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
     * Test Case: Memastikan Ketua KK dapat menyimpan plottingan pengajaran dengan sukses (mode solo, dosen punya jabatan).
     *
     * @return void
     */
    public function test_ketua_kk_can_store_plotting_successfully_solo_with_jabatan(): void
    {
        // 1. Persiapan Data
        $kelompokKeahlian = KelompokKeahlian::firstOrCreate(['id' => 1, 'nama' => 'KK A']); // Kelompok Keahlian untuk Ketua KK
        $ketuaKKUser = $this->createKetuaKKUser($kelompokKeahlian);
        $programStudi = ProgramStudi::firstOrCreate(['id' => 1, 'nama' => 'S1 Informatika']);

        $konversiSksJabatan = 2; // SKS dari jabatan struktural
        $jabatanStruktural = JabatanStruktural::firstOrCreate(['id' => 1, 'nama' => 'Ketua Jurusan', 'konversi_sks' => $konversiSksJabatan]);

        $dosen = Dosen::factory()->create([
            'id_kelompok_keahlian' => $kelompokKeahlian->id,
            'id_jabatan_struktural' => $jabatanStruktural->id, // Dosen memiliki jabatan struktural
        ]);

        // Perbaikan: PIC name harus cocok dengan nama Kelompok Keahlian Ketua KK
        $pic = Pic::firstOrCreate(['name' => $kelompokKeahlian->nama]);
        $sksMatakuliah = 3; // Mata kuliah 3 SKS
        $matakuliah = Matakuliah::factory()->create(['id_pic' => $pic->id, 'sks' => $sksMatakuliah]);
        $tahunAjaran = TahunAjaran::firstOrCreate(['id' => 1, 'tahun_ajaran' => '2024/2025', 'semester' => 'ganjil']);

        // Buat Mapping Kelas Mata Kuliah sebagai NON-TEAM TEACHING (solo)
        $mappingSolo = MappingKelasMatakuliah::factory()->create([
            'id_matakuliah' => $matakuliah->id,
            'id_tahun_ajaran' => $tahunAjaran->id,
            'id_program_studi' => $programStudi->id,
            'nama_kelas' => 'Solo Class With Jabatan',
            'team_teaching' => false, // Ini kuncinya: bukan team teaching
            'kuota' => 50,
        ]);

        $inputBebanSks = $sksMatakuliah; // Beban SKS penuh karena solo

        // 2. Aksi: Login sebagai Ketua KK dan kirim POST request untuk menyimpan plottingan
        $response = $this->actingAs($ketuaKKUser, 'sanctum')->postJson('/api/v1/plottingan-pengajaran/start-plottingan-pengajaran', [
            'id_dosen' => $dosen->id,
            'id_mapping_kelas_matakuliah' => $mappingSolo->id,
            'beban_sks' => $inputBebanSks,
            'id_program_studi' => $programStudi->id, // Diperlukan oleh validasi controller
        ]);

        // 3. Assertions
        $response->assertStatus(201) // Memastikan status HTTP 201 (Created)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Plottingan pengajaran berhasil disimpan.',
                     'data' => [
                         'id_dosen' => $dosen->id,
                         'id_mapping_kelas_matakuliah' => $mappingSolo->id,
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
            'id_mapping_kelas_matakuliah' => $mappingSolo->id,
            'beban_sks' => $inputBebanSks,
        ]);
    }

    /**
     * Test Case: Memastikan Ketua KK dapat menyimpan plottingan pengajaran dengan sukses (mode solo, dosen TIDAK punya jabatan).
     *
     * @return void
     */
    public function test_ketua_kk_can_store_plotting_successfully_solo_no_jabatan(): void
    {
        // 1. Persiapan Data
        $kelompokKeahlian = KelompokKeahlian::firstOrCreate(['id' => 1, 'nama' => 'KK A']);
        $ketuaKKUser = $this->createKetuaKKUser($kelompokKeahlian);
        $programStudi = ProgramStudi::firstOrCreate(['id' => 1, 'nama' => 'S1 Informatika']);

        $dosen = Dosen::factory()->create([
            'id_kelompok_keahlian' => $kelompokKeahlian->id,
            'id_jabatan_struktural' => null, // Dosen TIDAK memiliki jabatan struktural
        ]);

        // Perbaikan: PIC name harus cocok dengan nama Kelompok Keahlian Ketua KK
        $pic = Pic::firstOrCreate(['name' => $kelompokKeahlian->nama]);
        $sksMatakuliah = 3; // Mata kuliah 3 SKS
        $matakuliah = Matakuliah::factory()->create(['id_pic' => $pic->id, 'sks' => $sksMatakuliah]);
        $tahunAjaran = TahunAjaran::firstOrCreate(['id' => 1, 'tahun_ajaran' => '2024/2025', 'semester' => 'ganjil']);

        // Buat Mapping Kelas Mata Kuliah sebagai NON-TEAM TEACHING (solo)
        $mappingSolo = MappingKelasMatakuliah::factory()->create([
            'id_matakuliah' => $matakuliah->id,
            'id_tahun_ajaran' => $tahunAjaran->id,
            'id_program_studi' => $programStudi->id,
            'nama_kelas' => 'Solo Class No Jabatan',
            'team_teaching' => false, // Ini kuncinya: bukan team teaching
            'kuota' => 50,
        ]);

        $inputBebanSks = $sksMatakuliah; // Beban SKS penuh karena solo

        // 2. Aksi: Login sebagai Ketua KK dan kirim POST request untuk menyimpan plottingan
        $response = $this->actingAs($ketuaKKUser, 'sanctum')->postJson('/api/v1/plottingan-pengajaran/start-plottingan-pengajaran', [
            'id_dosen' => $dosen->id,
            'id_mapping_kelas_matakuliah' => $mappingSolo->id,
            'beban_sks' => $inputBebanSks,
            'id_program_studi' => $programStudi->id, // Diperlukan oleh validasi controller
        ]);

        // 3. Assertions
        $response->assertStatus(201) // Memastikan status HTTP 201 (Created)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Plottingan pengajaran berhasil disimpan.',
                     'data' => [
                         'id_dosen' => $dosen->id,
                         'id_mapping_kelas_matakuliah' => $mappingSolo->id,
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
            'id_mapping_kelas_matakuliah' => $mappingSolo->id,
            'beban_sks' => $inputBebanSks,
        ]);
    }

     public function test_ketua_kk_cannot_store_plotting_if_mapping_not_found(): void
    {
        // 1. Persiapan Data
        $kelompokKeahlian = KelompokKeahlian::firstOrCreate(['id' => 1, 'nama' => 'KK A']);
        $ketuaKKUser = $this->createKetuaKKUser($kelompokKeahlian);
        $dosen = Dosen::factory()->create([
            'id_kelompok_keahlian' => KelompokKeahlian::first()->id,
            'id_jabatan_struktural' => JabatanStruktural::first()->id,
        ]);
        $nonExistentMappingId = 9999;

        // 2. Aksi: Login sebagai Ketua KK dan kirim POST request
        $response = $this->actingAs($ketuaKKUser, 'sanctum')->postJson('/api/v1/plottingan-pengajaran/start-plottingan-pengajaran', [
            'id_dosen' => $dosen->id,
            'id_mapping_kelas_matakuliah' => $nonExistentMappingId,
            'beban_sks' => 3,
            'id_program_studi' => ProgramStudi::first()->id, // Diperlukan oleh validasi controller
        ]);

        // 3. Assertions
        $response->assertStatus(422)
                 ->assertJson([
                     'message' => 'The selected id mapping kelas matakuliah is invalid.',
                 ]);

        $this->assertDatabaseMissing('plottingan_pengajarans', [
            'id_dosen' => $dosen->id,
            'id_mapping_kelas_matakuliah' => $nonExistentMappingId,
        ]);
    }

     public function test_ketua_kk_cannot_store_plotting_if_matakuliah_soft_deleted_for_mapping(): void
    {
        // 1. Persiapan Data
        $kelompokKeahlian = KelompokKeahlian::firstOrCreate(['id' => 1, 'nama' => 'KK A']);
        $ketuaKKUser = $this->createKetuaKKUser($kelompokKeahlian);
        $dosen = Dosen::factory()->create([
            'id_kelompok_keahlian' => KelompokKeahlian::first()->id,
            'id_jabatan_struktural' => JabatanStruktural::first()->id,
        ]);

        // Buat PIC yang namanya cocok dengan Kelompok Keahlian Ketua KK
        $picMatchingKK = Pic::firstOrCreate(['name' => $kelompokKeahlian->nama]);
        $matakuliah = Matakuliah::factory()->create(['id_pic' => $picMatchingKK->id]);
        $tahunAjaran = TahunAjaran::firstOrCreate(['id' => 1, 'tahun_ajaran' => '2024/2025', 'semester' => 'ganjil']);
        $programStudi = ProgramStudi::firstOrCreate(['id' => 1, 'nama' => 'S1 Informatika']);

        $mapping = MappingKelasMatakuliah::factory()->create([
            'id_matakuliah' => $matakuliah->id,
            'id_tahun_ajaran' => $tahunAjaran->id,
            'id_program_studi' => $programStudi->id,
            'nama_kelas' => 'Valid MK Class',
        ]);

        $matakuliah->delete(); // Soft delete Matakuliah

        // 2. Aksi: Login sebagai Ketua KK dan kirim POST request
        $response = $this->actingAs($ketuaKKUser, 'sanctum')->postJson('/api/v1/plottingan-pengajaran/start-plottingan-pengajaran', [
            'id_dosen' => $dosen->id,
            'id_mapping_kelas_matakuliah' => $mapping->id,
            'beban_sks' => 3,
            'id_program_studi' => $programStudi->id,
        ]);

        // 3. Assertions
        $response->assertStatus(404)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Mata Kuliah terkait dengan mapping tidak ditemukan.',
                 ]);

        $this->assertDatabaseMissing('plottingan_pengajarans', [
            'id_dosen' => $dosen->id,
            'id_mapping_kelas_matakuliah' => $mapping->id,
        ]);
    }

    public function test_ketua_kk_cannot_store_plotting_if_kk_not_authorized_for_pic(): void
    {
        // 1. Persiapan Data
        $kelompokKeahlianKetuaKK = KelompokKeahlian::firstOrCreate(['id' => 1, 'nama' => 'SEAL']);
        $ketuaKKUser = $this->createKetuaKKUser($kelompokKeahlianKetuaKK);

        // Buat PIC yang TIDAK SESUAI dengan Kelompok Keahlian Ketua KK
        $picOtherKKName = 'CITI';
        $picOtherKK = Pic::factory()->create(['name' => $picOtherKKName]);

        $matakuliah = Matakuliah::factory()->create(['id_pic' => $picOtherKK->id]);
        $tahunAjaran = TahunAjaran::firstOrCreate(['id' => 1, 'tahun_ajaran' => '2024/2025', 'semester' => 'ganjil']);
        $programStudi = ProgramStudi::firstOrCreate(['id' => 1, 'nama' => 'S1 Informatika']);

        $mapping = MappingKelasMatakuliah::factory()->create([
            'id_matakuliah' => $matakuliah->id,
            'id_tahun_ajaran' => $tahunAjaran->id,
            'id_program_studi' => $programStudi->id,
            'nama_kelas' => 'Unauth MK Class',
        ]);

        $dosen = Dosen::factory()->create([
            'id_kelompok_keahlian' => KelompokKeahlian::first()->id,
            'id_jabatan_struktural' => JabatanStruktural::first()->id,
        ]);

        // 2. Aksi: Login sebagai Ketua KK dan kirim POST request
        $response = $this->actingAs($ketuaKKUser, 'sanctum')->postJson('/api/v1/plottingan-pengajaran/start-plottingan-pengajaran', [
            'id_dosen' => $dosen->id,
            'id_mapping_kelas_matakuliah' => $mapping->id,
            'beban_sks' => 3,
            'id_program_studi' => $programStudi->id,
        ]);

        // 3. Assertions
        $response->assertStatus(403)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Anda tidak berwenang melakukan plotting untuk mata kuliah dengan PIC (' . $picOtherKK->name . '). Program Studi/Kelompok Keahlian Anda tidak sesuai.',
                 ]);

        $this->assertDatabaseMissing('plottingan_pengajarans', [
            'id_dosen' => $dosen->id,
            'id_mapping_kelas_matakuliah' => $mapping->id,
        ]);
    }

     public function test_unauthorized_user_cannot_store_plotting_due_to_role(): void
    {
        // 1. Persiapan Data
        $unauthorizedUser = $this->createUnauthorizedPlottingUser(); // User dengan role LayananAkademik
        $dosen = Dosen::factory()->create([
            'id_kelompok_keahlian' => KelompokKeahlian::firstOrCreate(['id' => 1, 'nama' => 'KK A'])->id,
            'id_jabatan_struktural' => JabatanStruktural::firstOrCreate(['id' => 1, 'nama' => 'Jabatan A', 'konversi_sks' => 1])->id,
        ]);
        $pic = Pic::firstOrCreate(['id' => 1, 'name' => 'Default PIC']);
        $matakuliah = Matakuliah::factory()->create(['id_pic' => $pic->id]);
        $tahunAjaran = TahunAjaran::firstOrCreate(['id' => 1, 'tahun_ajaran' => '2024/2025', 'semester' => 'ganjil']);
        $programStudi = ProgramStudi::firstOrCreate(['id' => 1, 'nama' => 'S1 Informatika']);
        $mapping = MappingKelasMatakuliah::factory()->create([
            'id_matakuliah' => $matakuliah->id,
            'id_tahun_ajaran' => $tahunAjaran->id,
            'id_program_studi' => $programStudi->id,
        ]);

        // 2. Aksi: Login sebagai user tidak terotorisasi dan kirim POST request
        $response = $this->actingAs($unauthorizedUser, 'sanctum')->postJson('/api/v1/plottingan-pengajaran/start-plottingan-pengajaran', [
            'id_dosen' => $dosen->id,
            'id_mapping_kelas_matakuliah' => $mapping->id,
            'beban_sks' => 3,
        ]);

        // 3. Assertions
        $response->assertStatus(403) // Memastikan status HTTP 403 (Forbidden)
                 ->assertJson([
                     'message' => 'Forbidden: You do not have the required role.',
                 ]);

        // Pastikan tidak ada plottingan yang dibuat
        $this->assertDatabaseMissing('plottingan_pengajarans', [
            'id_dosen' => $dosen->id,
            'id_mapping_kelas_matakuliah' => $mapping->id,
        ]);
    }

    public function test_ketua_kk_cannot_store_plotting_if_solo_class_already_plotted(): void
    {
        // 1. Persiapan Data
        $kelompokKeahlian = KelompokKeahlian::firstOrCreate(['id' => 1, 'nama' => 'KK A']);
        $ketuaKKUser = $this->createKetuaKKUser($kelompokKeahlian);
        $programStudi = ProgramStudi::firstOrCreate(['id' => 1, 'nama' => 'S1 Informatika']);

        $dosenExisting = Dosen::factory()->create([ // Dosen yang sudah memplot kelas ini
            'id_kelompok_keahlian' => KelompokKeahlian::first()->id,
            'id_jabatan_struktural' => JabatanStruktural::first()->id,
        ]);
        $dosenNew = Dosen::factory()->create([ // Dosen yang mencoba memplot lagi
            'id_kelompok_keahlian' => KelompokKeahlian::first()->id,
            'id_jabatan_struktural' => JabatanStruktural::first()->id,
        ]);

        $pic = Pic::firstOrCreate(['name' => $kelompokKeahlian->nama]);// PIC name is 'S1 Informatika'
        $matakuliah = Matakuliah::factory()->create(['id_pic' => $pic->id, 'sks' => 3]);
        $tahunAjaran = TahunAjaran::firstOrCreate(['id' => 1, 'tahun_ajaran' => '2024/2025', 'semester' => 'ganjil']);

        // Buat Mapping Kelas Mata Kuliah sebagai NON-TEAM TEACHING (solo)
        $mappingSolo = MappingKelasMatakuliah::factory()->create([
            'id_matakuliah' => $matakuliah->id,
            'id_tahun_ajaran' => $tahunAjaran->id,
            'id_program_studi' => $programStudi->id,
            'nama_kelas' => 'Solo Class',
            'team_teaching' => false,
            'kuota' => 50,
        ]);

        // Plottingan yang sudah ada untuk kelas solo ini
        PlottinganPengajaran::create([
            'id_dosen' => $dosenExisting->id,
            'id_mapping_kelas_matakuliah' => $mappingSolo->id,
            'beban_sks' => $matakuliah->sks,
        ]);

        // 2. Aksi: Login sebagai Ketua KK dan coba plot kelas solo yang sudah ada
        $response = $this->actingAs($ketuaKKUser, 'sanctum')->postJson('/api/v1/plottingan-pengajaran/start-plottingan-pengajaran', [
            'id_dosen' => $dosenNew->id,
            'id_mapping_kelas_matakuliah' => $mappingSolo->id,
            'beban_sks' => $matakuliah->sks,
            'id_program_studi' => $programStudi->id, // Tambahkan ini agar middleware tidak 403
        ]);

        // 3. Assertions
        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Kelas ini (' . $mappingSolo->nama_kelas . ' - ' . $matakuliah->nama_matakuliah . ') sudah diplot ke dosen lain (' . $dosenExisting->name . ') karena bukan mode team teaching.',
                     'errors' => [
                         'id_mapping_kelas_matakuliah' => ['Kelas ini sudah memiliki dosen pengajar (mode solo).']
                     ]
                 ]);

        // Pastikan tidak ada plottingan baru yang dibuat
        $this->assertDatabaseMissing('plottingan_pengajarans', [
            'id_dosen' => $dosenNew->id,
            'id_mapping_kelas_matakuliah' => $mappingSolo->id,
        ]);
        // Pastikan plottingan lama tetap ada
        $this->assertDatabaseHas('plottingan_pengajarans', [
            'id_dosen' => $dosenExisting->id,
            'id_mapping_kelas_matakuliah' => $mappingSolo->id,
        ]);
    }

     public function test_ketua_kk_cannot_store_plotting_total_sks_exceeds_max_with_jabatan_team_teaching(): void
    {
        // 1. Persiapan Data
        $kelompokKeahlian = KelompokKeahlian::firstOrCreate(['id' => 1, 'nama' => 'KK A']);
        $ketuaKKUser = $this->createKetuaKKUser($kelompokKeahlian);
        $programStudi = ProgramStudi::firstOrCreate(['id' => 1, 'nama' => 'S1 Informatika']);

        $konversiSksJabatan = 5;
        $jabatanStruktural = JabatanStruktural::firstOrCreate(['id' => 1, 'nama' => 'Ketua Jurusan', 'konversi_sks' => $konversiSksJabatan]);

        $dosen = Dosen::factory()->create([
            'id_kelompok_keahlian' => $kelompokKeahlian->id,
            'id_jabatan_struktural' => $jabatanStruktural->id, // Dosen memiliki jabatan struktural
        ]);

        $pic = Pic::firstOrCreate(['name' => $kelompokKeahlian->nama]);
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
            'id_matakuliah' => $matakuliahNew->id, // Mata kuliah 6 SKS
            'id_tahun_ajaran' => $tahunAjaran->id,
            'id_program_studi' => $programStudi->id,
            'nama_kelas' => 'Kelas Exceed Total SKS',
            'team_teaching' => false,
            'kuota' => 50,
        ]);

        $inputBebanSksNew = $matakuliahNew->sks; // Beban SKS penuh dari MK baru (6 SKS)

        // 2. Aksi: Login sebagai Ketua KK dan coba plot yang akan melebihi batas SKS dosen
        $response = $this->actingAs($ketuaKKUser, 'sanctum')->postJson('/api/v1/plottingan-pengajaran/start-plottingan-pengajaran', [
            'id_dosen' => $dosen->id,
            'id_mapping_kelas_matakuliah' => $mappingNew->id,
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

    /**
     * Test Case: Memastikan Ketua KK gagal menyimpan plottingan karena total SKS dosen akan melebihi batas maksimal (dosen TIDAK punya jabatan, team teaching).
     * Skenario: Dosen tidak memiliki jabatan struktural, ada plottingan sebelumnya, dan plottingan baru akan membuat total SKS melebihi 16.
     * HTTP Response status code = 422 (Unprocessable Entity)
     *
     * @return void
     */
    public function test_ketua_kk_cannot_store_plotting_total_sks_exceeds_max_no_jabatan_solo(): void
    {
        // 1. Persiapan Data
        $kelompokKeahlian = KelompokKeahlian::firstOrCreate(['id' => 1, 'nama' => 'KK A']);
        $ketuaKKUser = $this->createKetuaKKUser($kelompokKeahlian);
        $programStudi = ProgramStudi::firstOrCreate(['id' => 1, 'nama' => 'S1 Informatika']);


        $konversiSksJabatan = 0; // Tidak ada SKS dari jabatan struktural
        $dosen = Dosen::factory()->create([
            'id_kelompok_keahlian' => $kelompokKeahlian->id,
            'id_jabatan_struktural' => null, // Dosen TIDAK memiliki jabatan struktural
        ]);

        $pic = Pic::firstOrCreate(['name' => $kelompokKeahlian->nama]);
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
            'team_teaching' => false,
            'kuota' => 50,
        ]);

        $inputBebanSksNew = $matakuliah->sks; // Beban SKS dari MK baru (4 SKS)

        // 2. Aksi: Login sebagai Ketua KK dan coba plot yang akan melebihi batas SKS
        $response = $this->actingAs($ketuaKKUser, 'sanctum')->postJson('/api/v1/plottingan-pengajaran/start-plottingan-pengajaran', [
            'id_dosen' => $dosen->id,
            'id_mapping_kelas_matakuliah' => $mappingNew->id,
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
