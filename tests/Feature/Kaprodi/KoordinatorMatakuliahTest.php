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
use App\Models\KoordinatorMatakuliah;
use App\Models\KelompokKeahlian;
use App\Models\Pic;

class KoordinatorMatakuliahTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['id' => 1, 'name' => 'Superadmin']);
        Role::firstOrCreate(['id' => 2, 'name' => 'ProgramStudi']);
        Role::firstOrCreate(['id' => 3, 'name' => 'KelompokKeahlian']);

        for ($i = 1; $i <= 5; $i++) {
            KelompokKeahlian::firstOrCreate(['id' => $i, 'nama' => 'KK ' . chr(64 + $i)]);
        }
        Pic::firstOrCreate(['id' => 1, 'name' => 'Default PIC']);
        ProgramStudi::firstOrCreate(['id' => 1, 'nama' => 'S1 Informatika']);
    }

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

    public function test_kaprodi_can_assign_koordinator_by_program_studi_successfully(): void
    {
        $dosenKoordinator = Dosen::factory()->create(['id_jabatan_struktural' => null, 'id_kelompok_keahlian' => KelompokKeahlian::first()->id]);
        $programStudi = ProgramStudi::factory()->create(['nama' => 'S1 Rekayasa Perangkat Lunak']);
        $tahunAjaran = TahunAjaran::factory()->create(['tahun_ajaran' => '2024/2025', 'semester' => 'ganjil']);
        $matakuliah = Matakuliah::factory()->create(['id_pic' => Pic::first()->id]);

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

        $kaprodiUser = $this->createProgramStudiUser($programStudi);

        $requestPayload = [
            'id_dosen' => $dosenKoordinator->id,
            'id_program_studi' => $programStudi->id,
            'id_tahun_ajaran' => $tahunAjaran->id,
            'id_matakuliah' => $matakuliah->id,
        ];

        $response = $this->actingAs($kaprodiUser, 'sanctum')->postJson('/api/v1/masterdata/koordinator-matakuliah/assign-by-program-studi', $requestPayload);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => '2 kelas mata kuliah untuk Program Studi yang dipilih berhasil di-assign/diperbarui koordinatornya.',
                 ]);
    }

    public function test_kaprodi_assign_koordinator_no_mapping_found(): void
    {
        $dosenKoordinator = Dosen::factory()->create(['id_jabatan_struktural' => null, 'id_kelompok_keahlian' => KelompokKeahlian::first()->id]);
        $programStudi = ProgramStudi::factory()->create(['nama' => 'S1 Data Sains']);
        $tahunAjaran = TahunAjaran::factory()->create(['tahun_ajaran' => '2024/2025', 'semester' => 'ganjil']);
        $matakuliah = Matakuliah::factory()->create(['id_pic' => Pic::first()->id]);

        $kaprodiUser = $this->createProgramStudiUser($programStudi);

        $requestPayload = [
            'id_dosen' => $dosenKoordinator->id,
            'id_program_studi' => $programStudi->id,
            'id_tahun_ajaran' => $tahunAjaran->id,
            'id_matakuliah' => $matakuliah->id,
        ];

        $this->assertDatabaseMissing('mapping_kelas_matakuliahs', [
            'id_matakuliah' => $matakuliah->id,
            'id_tahun_ajaran' => $tahunAjaran->id,
            'id_program_studi' => $programStudi->id,
        ]);

        $response = $this->actingAs($kaprodiUser, 'sanctum')->postJson('/api/v1/masterdata/koordinator-matakuliah/assign-by-program-studi', $requestPayload);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Tidak ada kelas mata kuliah yang ditemukan untuk Program Studi dan Tahun Ajaran yang dipilih. Tidak ada koordinator yang diassign.',
                     'data' => []
                 ]);


    }

    public function test_kaprodi_cannot_assign_koordinator_with_invalid_data(): void
    {
        // 1. Persiapan Data
        $programStudi = ProgramStudi::factory()->create(['nama' => 'S1 Data Sains Test']);
        $kaprodiUser = $this->createProgramStudiUser($programStudi);

        // Buat data valid yang akan digunakan sebagai dasar untuk skenario invalid
        $dosenKoordinator = Dosen::factory()->create(['id_jabatan_struktural' => null, 'id_kelompok_keahlian' => KelompokKeahlian::first()->id]);
        $tahunAjaran = TahunAjaran::factory()->create(['tahun_ajaran' => '2025/2026', 'semester' => 'genap']);
        $matakuliah = Matakuliah::factory()->create(['id_pic' => Pic::first()->id]);

        // Skenario 1: id_dosen tidak ada (validasi exists)
        $response1 = $this->actingAs($kaprodiUser, 'sanctum')->postJson('/api/v1/masterdata/koordinator-matakuliah/assign-by-program-studi', [
            'id_dosen' => 9999, // ID Dosen tidak ada
            'id_program_studi' => $programStudi->id,
            'id_tahun_ajaran' => $tahunAjaran->id,
            'id_matakuliah' => $matakuliah->id,
        ]);
        $response1->assertStatus(422)
                  ->assertJsonValidationErrors(['id_dosen']);

        // Skenario 2: id_program_studi tidak ada (validasi exists)
        $response2 = $this->actingAs($kaprodiUser, 'sanctum')->postJson('/api/v1/masterdata/koordinator-matakuliah/assign-by-program-studi', [
            'id_dosen' => $dosenKoordinator->id,
            'id_program_studi' => 9999, // ID Program Studi tidak ada
            'id_tahun_ajaran' => $tahunAjaran->id,
            'id_matakuliah' => $matakuliah->id,
        ]);
        $response2->assertStatus(422)
                  ->assertJsonValidationErrors(['id_program_studi']);

        // Skenario 3: id_tahun_ajaran tidak ada (validasi exists)
        $response3 = $this->actingAs($kaprodiUser, 'sanctum')->postJson('/api/v1/masterdata/koordinator-matakuliah/assign-by-program-studi', [
            'id_dosen' => $dosenKoordinator->id,
            'id_program_studi' => $programStudi->id,
            'id_tahun_ajaran' => 9999, // ID Tahun Ajaran tidak ada
            'id_matakuliah' => $matakuliah->id,
        ]);
        $response3->assertStatus(422)
                  ->assertJsonValidationErrors(['id_tahun_ajaran']);

        // Skenario 4: id_matakuliah tidak ada (validasi exists)
        $response4 = $this->actingAs($kaprodiUser, 'sanctum')->postJson('/api/v1/masterdata/koordinator-matakuliah/assign-by-program-studi', [
            'id_dosen' => $dosenKoordinator->id,
            'id_program_studi' => $programStudi->id,
            'id_tahun_ajaran' => $tahunAjaran->id,
            'id_matakuliah' => 9999, // ID Mata Kuliah tidak ada
        ]);
        $response4->assertStatus(422)
                  ->assertJsonValidationErrors(['id_matakuliah']);

        // Skenario 5: Payload kosong/tidak lengkap (validasi required)
        $response5 = $this->actingAs($kaprodiUser, 'sanctum')->postJson('/api/v1/masterdata/koordinator-matakuliah/assign-by-program-studi', []);
        $response5->assertStatus(422)
                  ->assertJsonValidationErrors(['id_dosen', 'id_program_studi', 'id_tahun_ajaran', 'id_matakuliah']);
    }

    
}
