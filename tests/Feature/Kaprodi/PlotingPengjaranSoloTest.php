<?php

namespace Tests\Feature\Kaprodi; // Namespace disesuaikan untuk path baru

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

class PlottingPengjaranTest extends TestCase
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
        // Pastikan ada PIC default, tapi untuk test tertentu kita akan buat PIC yang namanya match dengan Prodi
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

     public function test_kaprodi_can_store_plotting_successfully_with_jabatan(): void
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
        $matakuliah = Matakuliah::factory()->create(['id_pic' => $pic->id, 'sks' => 3]); // Mata kuliah 3 SKS
        $tahunAjaran = TahunAjaran::firstOrCreate(['id' => 1, 'tahun_ajaran' => '2024/2025', 'semester' => 'ganjil']);

        $mapping = MappingKelasMatakuliah::factory()->create([
            'id_matakuliah' => $matakuliah->id,
            'id_tahun_ajaran' => $tahunAjaran->id,
            'id_program_studi' => $programStudi->id,
            'nama_kelas' => 'Kelas A',
            'team_teaching' => false, // Non-team teaching
            'kuota' => 50,
        ]);

        // Hitung total SKS proyeksi awal (hanya jabatan)
        $totalSksMengajarDosenSebelumnya = 0; // Asumsi belum ada plottingan sebelumnya
        $maksimalSksDosen = 16; // Batas maksimal SKS dosen
        $inputBebanSks = $matakuliah->sks; // 3 SKS dari plottingan ini
        $totalSksProyeksi = $konversiSksJabatan + $totalSksMengajarDosenSebelumnya + $inputBebanSks; // 2 + 0 + 3 = 5 SKS (di bawah batas)

        // 2. Aksi: Login sebagai Kaprodi dan kirim POST request untuk menyimpan plottingan
        $response = $this->actingAs($kaprodiUser, 'sanctum')->postJson('/api/v1/plottingan-pengajaran/start-plottingan-pengajaran', [
            'id_dosen' => $dosen->id,
            'id_mapping_kelas_matakuliah' => $mapping->id,
            'beban_sks' => $inputBebanSks, // Beban SKS dari mata kuliah
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

     public function test_kaprodi_can_store_plotting_successfully_no_jabatan(): void
    {
        // 1. Persiapan Data
        $programStudi = ProgramStudi::firstOrCreate(['id' => 1, 'nama' => 'S1 Informatika']);
        $kaprodiUser = $this->createProgramStudiUser($programStudi);

        $dosen = Dosen::factory()->create([
            'id_kelompok_keahlian' => KelompokKeahlian::firstOrCreate(['id' => 1, 'nama' => 'KK A'])->id,
            'id_jabatan_struktural' => null, // Dosen TIDAK memiliki jabatan struktural
        ]);

        $pic = Pic::firstOrCreate(['name' => $programStudi->nama]);
        $matakuliah = Matakuliah::factory()->create(['id_pic' => $pic->id, 'sks' => 3]); // Mata kuliah 3 SKS
        $tahunAjaran = TahunAjaran::firstOrCreate(['id' => 1, 'tahun_ajaran' => '2024/2025', 'semester' => 'ganjil']);

        $mapping = MappingKelasMatakuliah::factory()->create([
            'id_matakuliah' => $matakuliah->id,
            'id_tahun_ajaran' => $tahunAjaran->id,
            'id_program_studi' => $programStudi->id,
            'nama_kelas' => 'Kelas B',
            'team_teaching' => false, // Non-team teaching
            'kuota' => 50,
        ]);

        $inputBebanSks = $matakuliah->sks; // 3 SKS dari plottingan ini

        // 2. Aksi: Login sebagai Kaprodi dan kirim POST request untuk menyimpan plottingan
        $response = $this->actingAs($kaprodiUser, 'sanctum')->postJson('/api/v1/plottingan-pengajaran/start-plottingan-pengajaran', [
            'id_dosen' => $dosen->id,
            'id_mapping_kelas_matakuliah' => $mapping->id,
            'beban_sks' => $inputBebanSks,
            'id_program_studi' => $programStudi->id,
        ]);

        // 3. Assertions
        $response->assertStatus(201)
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

        $this->assertDatabaseHas('plottingan_pengajarans', [
            'id_dosen' => $dosen->id,
            'id_mapping_kelas_matakuliah' => $mapping->id,
            'beban_sks' => $inputBebanSks,
        ]);
    }

    /**
     * Test Case: Memastikan Kaprodi gagal menyimpan plottingan jika Mapping Kelas Mata Kuliah tidak ditemukan.
     * HTTP Response status code = 404 (Not Found)
     *
     * @return void
     */
    public function test_kaprodi_cannot_store_plotting_if_mapping_not_found(): void
    {
        // 1. Persiapan Data
        $programStudi = ProgramStudi::first();
        $kaprodiUser = $this->createProgramStudiUser($programStudi);
        $dosen = Dosen::factory()->create(); // Dosen yang akan diplot
        $nonExistentMappingId = 9999; // ID Mapping Kelas Mata Kuliah yang pasti tidak ada

        // 2. Aksi: Login sebagai Kaprodi dan kirim POST request
        $response = $this->actingAs($kaprodiUser, 'sanctum')->postJson('/api/v1/plottingan-pengajaran/start-plottingan-pengajaran', [
            'id_dosen' => $dosen->id,
            'id_mapping_kelas_matakuliah' => $nonExistentMappingId,
            'beban_sks' => 3, // Beban SKS bisa null, tapi kita isi valid
        ]);

        // 3. Assertions
        $response->assertStatus(422)
                 ->assertJson([
                     'message' => 'The selected id mapping kelas matakuliah is invalid.',
                 ]);

        // Pastikan tidak ada plottingan yang dibuat
        $this->assertDatabaseMissing('plottingan_pengajarans', [
            'id_dosen' => $dosen->id,
            'id_mapping_kelas_matakuliah' => $nonExistentMappingId,
        ]);
    }

     public function test_kaprodi_cannot_store_plotting_if_matakuliah_soft_deleted_for_mapping(): void
    {
        // 1. Persiapan Data
        $programStudi = ProgramStudi::first();
        $kaprodiUser = $this->createProgramStudiUser($programStudi);
        $dosen = Dosen::factory()->create();

        // Buat Matakuliah dan Mapping Kelas Mata Kuliah yang valid
        $matakuliah = Matakuliah::factory()->create(['id_pic' => Pic::first()->id]);
        $tahunAjaran = TahunAjaran::first();
        $mapping = MappingKelasMatakuliah::factory()->create([
            'id_matakuliah' => $matakuliah->id,
            'id_tahun_ajaran' => $tahunAjaran->id,
            'id_program_studi' => $programStudi->id,
            'nama_kelas' => 'Valid MK Class',
        ]);

        // --- Perbaikan: Soft delete Matakuliah setelah Mapping dibuat ---
        $matakuliah->delete(); // Ini akan mengisi kolom 'deleted_at'
        // --- Akhir Perbaikan ---

        // 2. Aksi: Login sebagai Kaprodi dan kirim POST request
        $response = $this->actingAs($kaprodiUser, 'sanctum')->postJson('/api/v1/plottingan-pengajaran/start-plottingan-pengajaran', [
            'id_dosen' => $dosen->id,
            'id_mapping_kelas_matakuliah' => $mapping->id,
            'beban_sks' => 3,
        ]);

        // 3. Assertions
        $response->assertStatus(404) // Memastikan status HTTP 404
                 ->assertJson([
                     'success' => false,
                     'message' => 'Mata Kuliah terkait dengan mapping tidak ditemukan.',
                 ]);

        // Pastikan tidak ada plottingan yang dibuat
        $this->assertDatabaseMissing('plottingan_pengajarans', [
            'id_dosen' => $dosen->id,
            'id_mapping_kelas_matakuliah' => $mapping->id,
        ]);
    }

    public function test_kaprodi_cannot_store_plotting_if_prodi_not_authorized_for_pic(): void
    {
        // 1. Persiapan Data
        // Buat Program Studi untuk Kaprodi
        $kaprodiProdi = ProgramStudi::factory()->create(['nama' => 'S1 Rekayasa Perangkat Lunak']);
        $kaprodiUser = $this->createProgramStudiUser($kaprodiProdi);

        // Buat PIC yang TIDAK SESUAI dengan Program Studi Kaprodi
        $picOtherProdi = Pic::factory()->create(['name' => 'S1 Data Sains']); // Nama PIC ini tidak cocok dengan nama prodi kaprodi

        // Buat Mata Kuliah yang PIC-nya tidak sesuai
        $matakuliah = Matakuliah::factory()->create(['id_pic' => $picOtherProdi->id]);
        $tahunAjaran = TahunAjaran::first();

        // Buat Mapping Kelas Mata Kuliah yang valid secara ID, tapi PIC-nya tidak sesuai dengan Kaprodi
        $mapping = MappingKelasMatakuliah::factory()->create([
            'id_matakuliah' => $matakuliah->id,
            'id_tahun_ajaran' => $tahunAjaran->id,
            'id_program_studi' => $kaprodiProdi->id, // Mapping ini terhubung ke prodi kaprodi
        ]);

        $dosen = Dosen::factory()->create();

        // 2. Aksi: Login sebagai Kaprodi dan kirim POST request
        $response = $this->actingAs($kaprodiUser, 'sanctum')->postJson('/api/v1/plottingan-pengajaran/start-plottingan-pengajaran', [
            'id_dosen' => $dosen->id,
            'id_mapping_kelas_matakuliah' => $mapping->id,
            'beban_sks' => 3,
        ]);

        // 3. Assertions
        $response->assertStatus(403) // Memastikan status HTTP 403 (Forbidden)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Anda tidak berwenang melakukan plotting untuk mata kuliah dengan PIC (' . $picOtherProdi->name . '). Program Studi/Kelompok Keahlian Anda tidak sesuai.',
                 ]);

        // Pastikan tidak ada plottingan yang dibuat
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

   public function test_kaprodi_cannot_store_plotting_if_solo_class_already_plotted(): void
    {
        // 1. Persiapan Data
        $programStudi = ProgramStudi::firstOrCreate(['id' => 1, 'nama' => 'S1 Informatika']);
        $kaprodiUser = $this->createProgramStudiUser($programStudi);

        $dosenExisting = Dosen::factory()->create([ // Dosen yang sudah memplot kelas ini
            'id_kelompok_keahlian' => KelompokKeahlian::firstOrCreate(['id' => 1, 'nama' => 'KK A'])->id,
            'id_jabatan_struktural' => JabatanStruktural::firstOrCreate(['id' => 1, 'nama' => 'Jabatan A', 'konversi_sks' => 1])->id,
        ]);
        $dosenNew = Dosen::factory()->create([ // Dosen yang mencoba memplot lagi
            'id_kelompok_keahlian' => KelompokKeahlian::firstOrCreate(['id' => 2, 'nama' => 'KK B'])->id,
            'id_jabatan_struktural' => JabatanStruktural::firstOrCreate(['id' => 2, 'nama' => 'Jabatan B', 'konversi_sks' => 2])->id,
        ]);

        // Perbaikan: Pastikan Pic name cocok dengan ProgramStudi name untuk otorisasi
        $pic = Pic::firstOrCreate(['name' => $programStudi->nama]); // PIC name is 'S1 Informatika'
        $matakuliah = Matakuliah::factory()->create(['id_pic' => $pic->id, 'sks' => 3]);
        $tahunAjaran = TahunAjaran::firstOrCreate(['id' => 1, 'tahun_ajaran' => '2024/2025', 'semester' => 'ganjil']);

        // Buat Mapping Kelas Mata Kuliah sebagai NON-TEAM TEACHING (solo)
        $mappingSolo = MappingKelasMatakuliah::factory()->create([
            'id_matakuliah' => $matakuliah->id,
            'id_tahun_ajaran' => $tahunAjaran->id,
            'id_program_studi' => $programStudi->id,
            'nama_kelas' => 'Solo Class',
            'team_teaching' => false, // Ini kuncinya: bukan team teaching
            'kuota' => 50,
        ]);

        // Plottingan yang sudah ada untuk kelas solo ini
        PlottinganPengajaran::create([
            'id_dosen' => $dosenExisting->id,
            'id_mapping_kelas_matakuliah' => $mappingSolo->id,
            'beban_sks' => $matakuliah->sks, // SKS penuh karena solo
        ]);

        // 2. Aksi: Login sebagai Kaprodi dan coba plot kelas solo yang sudah ada
        $response = $this->actingAs($kaprodiUser, 'sanctum')->postJson('/api/v1/plottingan-pengajaran/start-plottingan-pengajaran', [
            'id_dosen' => $dosenNew->id, // Dosen baru mencoba memplot
            'id_mapping_kelas_matakuliah' => $mappingSolo->id,
            'beban_sks' => $matakuliah->sks, // Beban SKS penuh
            'id_program_studi' => $programStudi->id, // Tambahkan ini agar middleware tidak 403
        ]);

        // 3. Assertions
        $response->assertStatus(422) // Memastikan status HTTP 422
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

     public function test_kaprodi_cannot_store_plotting_if_total_sks_exceeds_max(): void
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
        $matakuliah = Matakuliah::factory()->create(['id_pic' => $pic->id, 'sks' => 4]); // Mata kuliah 4 SKS
        $tahunAjaran = TahunAjaran::firstOrCreate(['id' => 1, 'tahun_ajaran' => '2024/2025', 'semester' => 'ganjil']);

        // Buat plottingan sebelumnya untuk dosen ini agar total SKS-nya tinggi
        $mappingExisting = MappingKelasMatakuliah::factory()->create([
            'id_matakuliah' => Matakuliah::factory()->create(['id_pic' => $pic->id, 'sks' => 8])->id, // MK 8 SKS
            'id_tahun_ajaran' => $tahunAjaran->id,
            'id_program_studi' => $programStudi->id,
            'team_teaching' => false,
        ]);
        PlottinganPengajaran::create([
            'id_dosen' => $dosen->id,
            'id_mapping_kelas_matakuliah' => $mappingExisting->id,
            'beban_sks' => 8, // Dosen sudah mengajar 8 SKS
        ]);

        // Total SKS proyeksi: 5 (jabatan) + 8 (mengajar sebelumnya) = 13 SKS
        // Maksimal SKS Dosen = 16 SKS
        // Plottingan baru: 4 SKS
        // Proyeksi akhir: 13 + 4 = 17 SKS (melebihi batas 16 SKS)

        $mappingNew = MappingKelasMatakuliah::factory()->create([
            'id_matakuliah' => $matakuliah->id, // Mata kuliah 4 SKS
            'id_tahun_ajaran' => $tahunAjaran->id,
            'id_program_studi' => $programStudi->id,
            'nama_kelas' => 'Kelas Z',
            'team_teaching' => false,
            'kuota' => 50,
        ]);

        // 2. Aksi: Login sebagai Kaprodi dan coba plot yang akan melebihi batas SKS
        $response = $this->actingAs($kaprodiUser, 'sanctum')->postJson('/api/v1/plottingan-pengajaran/start-plottingan-pengajaran', [
            'id_dosen' => $dosen->id,
            'id_mapping_kelas_matakuliah' => $mappingNew->id,
            'beban_sks' => $matakuliah->sks, // 4 SKS
            'id_program_studi' => $programStudi->id,
        ]);

        // 3. Assertions
        $maksimalSksDosen = 16; // Sesuai dengan logika controller
        $totalSksMengajarDosen = 8; // SKS dari plottingan sebelumnya
        $inputBebanSks = $matakuliah->sks; // 4 SKS dari plottingan ini
        $totalSksProyeksi = $konversiSksJabatan + $totalSksMengajarDosen + $inputBebanSks; // 5 + 8 + 4 = 17

        $response->assertStatus(422) // Memastikan status HTTP 422
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

    public function test_kaprodi_cannot_store_plotting_if_total_sks_exceeds_max_no_jabatan(): void
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
        $matakuliah = Matakuliah::factory()->create(['id_pic' => $pic->id, 'sks' => 4]); // Mata kuliah 4 SKS
        $tahunAjaran = TahunAjaran::firstOrCreate(['id' => 1, 'tahun_ajaran' => '2024/2025', 'semester' => 'ganjil']);

        // Buat plottingan sebelumnya untuk dosen ini agar total SKS-nya tinggi
        $mappingExisting = MappingKelasMatakuliah::factory()->create([
            'id_matakuliah' => Matakuliah::factory()->create(['id_pic' => $pic->id, 'sks' => 13])->id, // MK 13 SKS
            'id_tahun_ajaran' => $tahunAjaran->id,
            'id_program_studi' => $programStudi->id,
            'team_teaching' => false,
        ]);
        PlottinganPengajaran::create([
            'id_dosen' => $dosen->id,
            'id_mapping_kelas_matakuliah' => $mappingExisting->id,
            'beban_sks' => 13, // Dosen sudah mengajar 13 SKS
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

        // 2. Aksi: Login sebagai Kaprodi dan coba plot yang akan melebihi batas SKS
        $response = $this->actingAs($kaprodiUser, 'sanctum')->postJson('/api/v1/plottingan-pengajaran/start-plottingan-pengajaran', [
            'id_dosen' => $dosen->id,
            'id_mapping_kelas_matakuliah' => $mappingNew->id,
            'beban_sks' => $matakuliah->sks, // 4 SKS
            'id_program_studi' => $programStudi->id,
        ]);

        // 3. Assertions
        $maksimalSksDosen = 16;
        $totalSksMengajarDosen = 13;
        $inputBebanSks = $matakuliah->sks;
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
