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
use App\Models\KoordinatorMatakuliah;
use App\Models\KelompokKeahlian;
use App\Models\JabatanStruktural;
use App\Models\Pic;
use Illuminate\Support\Facades\DB;

class DosenKoordinatorTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

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
            'roleable_type' => KelompokKeahlian::class,
            'roleable_id' => $kelompokKeahlian->id,
        ]);
        return $user;
    }

    /**
     * Test Case: Memastikan Ketua KK dapat menetapkan dosen koordinator untuk MK khusus prodi.
     * HTTP Response status code = 200 (Success)
     *
     * @return void
     */
    public function test_ketua_kk_can_assign_koordinator_by_program_studi_successfully(): void
    {
        // 1. Persiapan Data
        $kelompokKeahlianKetuaKK = KelompokKeahlian::factory()->create(['nama' => 'SEAL']); // KK untuk Ketua KK
        $ketuaKKUser = $this->createKetuaKKUser($kelompokKeahlianKetuaKK); // User Ketua KK

        $dosenKoordinator = Dosen::factory()->create([
            'id_jabatan_struktural' => JabatanStruktural::first()->id,
            'id_kelompok_keahlian' => KelompokKeahlian::first()->id,
        ]);
        $programStudi = ProgramStudi::factory()->create(['nama' => 'S1 Rekayasa Perangkat Lunak']);
        $tahunAjaran = TahunAjaran::factory()->create(['tahun_ajaran' => '2024/2025', 'semester' => 'ganjil']);

        // Buat PIC yang namanya cocok dengan Kelompok Keahlian Ketua KK
        $picMatchingKK = Pic::factory()->create(['name' => $kelompokKeahlianKetuaKK->nama]);
        $matakuliah = Matakuliah::factory()->create(['id_pic' => $picMatchingKK->id]); // Mata Kuliah yang PIC-nya cocok

        // Buat beberapa MappingKelasMatakuliah yang akan diproses
        $mapping1 = MappingKelasMatakuliah::factory()->create([
            'id_matakuliah' => $matakuliah->id,
            'id_tahun_ajaran' => $tahunAjaran->id,
            'id_program_studi' => $programStudi->id,
            'nama_kelas' => 'A',
            'kuota' => 50,
            'team_teaching' => false,
        ]);
        $mapping2 = MappingKelasMatakuliah::factory()->create([
            'id_matakuliah' => $matakuliah->id,
            'id_tahun_ajaran' => $tahunAjaran->id,
            'id_program_studi' => $programStudi->id,
            'nama_kelas' => 'B',
            'kuota' => 45,
            'team_teaching' => true,
        ]);

        $requestPayload = [
            'id_dosen' => $dosenKoordinator->id,
            'id_program_studi' => $programStudi->id,
            'id_tahun_ajaran' => $tahunAjaran->id,
            'id_matakuliah' => $matakuliah->id,
        ];

        // 2. Aksi: Login sebagai user Ketua KK dan kirim POST request
        $response = $this->actingAs($ketuaKKUser, 'sanctum')->postJson('/api/v1/masterdata/koordinator-matakuliah/assign-by-program-studi', $requestPayload);

        // 3. Assertions
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => '2 kelas mata kuliah untuk Program Studi yang dipilih berhasil di-assign/diperbarui koordinatornya.',
                 ]);

        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => [
                    'id',
                    'id_dosen',
                    'id_mapping_kelas_matakuliah',
                    'dosen' => ['id', 'name', 'lecturer_code'],
                    'mapping_kelas_matakuliah' => [
                        'id', 'nama_kelas', 'id_matakuliah',
                        'matakuliah' => ['id', 'nama_matakuliah', 'kode_matkul']
                    ]
                ]
            ]
        ]);

        // Verifikasi database: memastikan koordinator telah di-assign/diperbarui untuk setiap mapping
        $this->assertDatabaseHas('koordinator_matakuliahs', [
            'id_mapping_kelas_matakuliah' => $mapping1->id,
            'id_dosen' => $dosenKoordinator->id,
        ]);
        $this->assertDatabaseHas('koordinator_matakuliahs', [
            'id_mapping_kelas_matakuliah' => $mapping2->id,
            'id_dosen' => $dosenKoordinator->id,
        ]);
    }

    public function test_ketua_kk_cannot_assign_koordinator_with_invalid_data(): void
    {
        // 1. Persiapan Data
        $kelompokKeahlianKetuaKK = KelompokKeahlian::factory()->create(['nama' => 'SEAL']);
        $ketuaKKUser = $this->createKetuaKKUser($kelompokKeahlianKetuaKK);

        $dosenKoordinator = Dosen::factory()->create([
            'id_jabatan_struktural' => JabatanStruktural::first()->id,
            'id_kelompok_keahlian' => KelompokKeahlian::first()->id,
        ]);
        $programStudi = ProgramStudi::factory()->create(['nama' => 'S1 Rekayasa Perangkat Lunak']);
        $tahunAjaran = TahunAjaran::factory()->create(['tahun_ajaran' => '2024/2025', 'semester' => 'ganjil']);
        $picMatchingKK = Pic::factory()->create(['name' => $kelompokKeahlianKetuaKK->nama]);
        $matakuliah = Matakuliah::factory()->create(['id_pic' => $picMatchingKK->id]);

        // Skenario 1: id_dosen tidak ada (validasi exists)
        $response1 = $this->actingAs($ketuaKKUser, 'sanctum')->postJson('/api/v1/masterdata/koordinator-matakuliah/assign-by-program-studi', [
            'id_dosen' => 9999, // ID Dosen tidak ada
            'id_program_studi' => $programStudi->id,
            'id_tahun_ajaran' => $tahunAjaran->id,
            'id_matakuliah' => $matakuliah->id,
        ]);
        $response1->assertStatus(422)
                  ->assertJsonValidationErrors(['id_dosen']);

        // Skenario 2: id_program_studi tidak ada (validasi exists)
        $response2 = $this->actingAs($ketuaKKUser, 'sanctum')->postJson('/api/v1/masterdata/koordinator-matakuliah/assign-by-program-studi', [
            'id_dosen' => $dosenKoordinator->id,
            'id_program_studi' => 9999, // ID Program Studi tidak ada
            'id_tahun_ajaran' => $tahunAjaran->id,
            'id_matakuliah' => $matakuliah->id,
        ]);
        $response2->assertStatus(422)
                  ->assertJsonValidationErrors(['id_program_studi']);

        // Skenario 3: id_tahun_ajaran tidak ada (validasi exists)
        $response3 = $this->actingAs($ketuaKKUser, 'sanctum')->postJson('/api/v1/masterdata/koordinator-matakuliah/assign-by-program-studi', [
            'id_dosen' => $dosenKoordinator->id,
            'id_program_studi' => $programStudi->id,
            'id_tahun_ajaran' => 9999, // ID Tahun Ajaran tidak ada
            'id_matakuliah' => $matakuliah->id,
        ]);
        $response3->assertStatus(422)
                  ->assertJsonValidationErrors(['id_tahun_ajaran']);

        // Skenario 4: id_matakuliah tidak ada (validasi exists)
        $response4 = $this->actingAs($ketuaKKUser, 'sanctum')->postJson('/api/v1/masterdata/koordinator-matakuliah/assign-by-program-studi', [
            'id_dosen' => $dosenKoordinator->id,
            'id_program_studi' => $programStudi->id,
            'id_tahun_ajaran' => $tahunAjaran->id,
            'id_matakuliah' => 9999, // ID Mata Kuliah tidak ada
        ]);
        $response4->assertStatus(422)
                  ->assertJsonValidationErrors(['id_matakuliah']);

        // Skenario 5: Payload kosong/tidak lengkap (validasi required)
        $response5 = $this->actingAs($ketuaKKUser, 'sanctum')->postJson('/api/v1/masterdata/koordinator-matakuliah/assign-by-program-studi', []);
        $response5->assertStatus(422)
                  ->assertJsonValidationErrors(['id_dosen', 'id_program_studi', 'id_tahun_ajaran', 'id_matakuliah']);
    }

    public function test_ketua_kk_assign_koordinator_no_mapping_found(): void
    {
        // 1. Persiapan Data
        $kelompokKeahlianKetuaKK = KelompokKeahlian::factory()->create(['nama' => 'DSIS']);
        $ketuaKKUser = $this->createKetuaKKUser($kelompokKeahlianKetuaKK);

        $dosenKoordinator = Dosen::factory()->create([
            'id_jabatan_struktural' => JabatanStruktural::first()->id,
            'id_kelompok_keahlian' => KelompokKeahlian::first()->id,
        ]);
        $programStudi = ProgramStudi::factory()->create(['nama' => 'S1 Data Sains']);
        $tahunAjaran = TahunAjaran::factory()->create(['tahun_ajaran' => '2024/2025', 'semester' => 'ganjil']);

        // Buat PIC yang namanya cocok dengan Kelompok Keahlian Ketua KK
        $picMatchingKK = Pic::factory()->create(['name' => $kelompokKeahlianKetuaKK->nama]);
        $matakuliah = Matakuliah::factory()->create(['id_pic' => $picMatchingKK->id]);

        $requestPayload = [
            'id_dosen' => $dosenKoordinator->id,
            'id_program_studi' => $programStudi->id,
            'id_tahun_ajaran' => $tahunAjaran->id,
            'id_matakuliah' => $matakuliah->id,
        ];

        // Pastikan tidak ada MappingKelasMatakuliah yang cocok dengan kriteria ini
        $this->assertDatabaseMissing('mapping_kelas_matakuliahs', [
            'id_matakuliah' => $matakuliah->id,
            'id_tahun_ajaran' => $tahunAjaran->id,
            'id_program_studi' => $programStudi->id,
        ]);

        // 2. Aksi: Kirim POST request
        $response = $this->actingAs($ketuaKKUser, 'sanctum')->postJson('/api/v1/masterdata/koordinator-matakuliah/assign-by-program-studi', $requestPayload);

        // 3. Assertions
        $response->assertStatus(200) // Memastikan status HTTP 200
                 ->assertJson([
                     'success' => true,
                     'message' => 'Tidak ada kelas mata kuliah yang ditemukan untuk Program Studi dan Tahun Ajaran yang dipilih. Tidak ada koordinator yang diassign.',
                     'data' => []
                 ]);

        // Pastikan tidak ada entri KoordinatorMatakuliah yang dibuat
        $this->assertDatabaseMissing('koordinator_matakuliahs', [
            'id_dosen' => $dosenKoordinator->id,
        ]);
    }
    
}
