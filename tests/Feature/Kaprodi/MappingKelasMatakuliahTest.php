<?php

namespace Tests\Feature\Kaprodi;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\User_Role;
use App\Models\Matakuliah;
use App\Models\TahunAjaran;
use App\Models\ProgramStudi;
use App\Models\KelompokKeahlian;
use App\Models\Pic;
use App\Models\MappingKelasMatakuliah;

class MappingKelasMatakuliahTest extends TestCase
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

        Pic::firstOrCreate(['id' => 1, 'name' => 'Default PIC']);
        ProgramStudi::firstOrCreate(['id' => 1, 'nama' => 'S1 Informatika']);
        KelompokKeahlian::firstOrCreate(['id' => 1, 'nama' => 'Default KK']);
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

    /**
     * Helper method untuk membuat user ProgramStudi yang tidak ter-assign ke Program Studi tertentu.
     *
     * @return \App\Models\User
     */
    protected function createUnassignedProgramStudiUser()
    {
        $user = User::factory()->create();
        $prodiRole = Role::where('name', 'ProgramStudi')->first();
        User_Role::create([
            'user_id' => $user->id,
            'role_id' => $prodiRole->id,
            'roleable_type' => null, // Tidak ter-assign ke entitas ProgramStudi
            'roleable_id' => null,
        ]);
        return $user;
    }

    public function test_program_studi_user_can_create_mapping_kelas_matakuliah_successfully(): void
    {
        $matakuliah = Matakuliah::factory()->create([
            'id_pic' => Pic::first()->id,
        ]);
        $tahunAjaran = TahunAjaran::factory()->create(['tahun_ajaran' => '2025/2026', 'semester' => 'genap']);
        $programStudi = ProgramStudi::factory()->create(['nama' => 'S1 Rekayasa Perangkat Lunak']);
        $prodiUser = $this->createProgramStudiUser($programStudi);

        $classData = [
            [
                'nama_kelas' => 'C',
                'kuota' => 30,
                'team_teaching' => false,
            ],
        ];

        $requestPayload = [
            'id_matakuliah' => $matakuliah->id,
            'id_tahun_ajaran' => $tahunAjaran->id,
            'classes' => $classData,
        ];

        $response = $this->actingAs($prodiUser, 'sanctum')->postJson('/api/v1/masterdata/mappingkelasmatakuliahs', $requestPayload);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'message' => count($classData) . ' mapping kelas mata kuliah berhasil ditambahkan.',
                 ]);

        foreach ($classData as $class) {
            $this->assertDatabaseHas('mapping_kelas_matakuliahs', [
                'id_matakuliah' => $matakuliah->id,
                'id_tahun_ajaran' => $tahunAjaran->id,
                'id_program_studi' => $programStudi->id,
                'nama_kelas' => $class['nama_kelas'],
                'kuota' => $class['kuota'],
                'team_teaching' => $class['team_teaching'],
            ]);
        }
    }

    public function test_kaprodi_cannot_create_mapping_if_unauthorized(): void
    {
        // 1. Persiapan Data
        // Buat user yang bukan Superadmin dan tidak ter-assign ke Program Studi
        $unauthorizedUser = User::factory()->create();
        $layananAkademikRole = Role::where('name', 'LayananAkademik')->first();
        User_Role::create([
            'user_id' => $unauthorizedUser->id,
            'role_id' => $layananAkademikRole->id,
            'roleable_type' => null,
            'roleable_id' => null,
        ]);

        $matakuliah = Matakuliah::factory()->create([
            'id_pic' => Pic::first()->id,
        ]);
        $tahunAjaran = TahunAjaran::factory()->create(['tahun_ajaran' => '2027/2028', 'semester' => 'ganjil']);
        $programStudi = ProgramStudi::factory()->create(['nama' => 'S1 Teknik Elektro']);

        $classData = [
            [
                'nama_kelas' => 'Z',
                'kuota' => 20,
                'team_teaching' => false,
            ],
        ];

        $requestPayload = [
            'id_matakuliah' => $matakuliah->id,
            'id_tahun_ajaran' => $tahunAjaran->id,
            'id_program_studi' => $programStudi->id, // Kirim id_program_studi meskipun tidak relevan untuk otorisasi
            'classes' => $classData,
        ];

        // 2. Aksi: Login sebagai user yang tidak terotorisasi dan kirim POST request
        $response = $this->actingAs($unauthorizedUser, 'sanctum')->postJson('/api/v1/masterdata/mappingkelasmatakuliahs', $requestPayload);

        // 3. Assertions
        $response->assertStatus(403) // Memastikan status HTTP 403 (Forbidden)
                 ->assertJson([
                     // Perbaikan: Sesuaikan pesan 'message' dengan yang dikembalikan middleware
                     'message' => 'Forbidden: You do not have the required role.',
                 ]);

        // Verifikasi database: memastikan tidak ada mapping yang dibuat
        $this->assertDatabaseMissing('mapping_kelas_matakuliahs', [
            'id_matakuliah' => $matakuliah->id,
            'id_tahun_ajaran' => $tahunAjaran->id,
            'id_program_studi' => $programStudi->id,
            'nama_kelas' => 'Z',
        ]);
    }

    /**
     * Test Case: Memastikan Kaprodi gagal melakukan Mapping Kelas MK (Program Studi tidak dapat ditentukan).
     * Skenario: User dengan role 'ProgramStudi' tetapi tanpa 'roleable_id' mencoba membuat mapping tanpa menyediakan 'id_program_studi'.
     * HTTP Response status code = 403 (Forbidden)
     *
     * @return void
     */
    public function test_kaprodi_cannot_create_mapping_if_prodi_undetermined_for_kaprodi(): void
    {
        // 1. Persiapan Data
        $unassignedProdiUser = $this->createUnassignedProgramStudiUser(); // User Kaprodi tanpa Program Studi ter-assign
        $matakuliah = Matakuliah::factory()->create([
            'id_pic' => Pic::first()->id,
        ]);
        $tahunAjaran = TahunAjaran::factory()->create(['tahun_ajaran' => '2028/2029', 'semester' => 'genap']);

        $classData = [
            [
                'nama_kelas' => 'Y',
                'kuota' => 22,
                'team_teaching' => false,
            ],
        ];

        $requestPayload = [
            'id_matakuliah' => $matakuliah->id,
            'id_tahun_ajaran' => $tahunAjaran->id,
            // id_program_studi TIDAK DIKIRIM dan user tidak ter-assign
            'classes' => $classData,
        ];

        // 2. Aksi: Login sebagai user Kaprodi yang tidak ter-assign dan kirim POST request
        $response = $this->actingAs($unassignedProdiUser, 'sanctum')->postJson('/api/v1/masterdata/mappingkelasmatakuliahs', $requestPayload);

        // 3. Assertions
        // Perbaikan: Ubah ekspektasi status dari 422 menjadi 403
        $response->assertStatus(403) // Memastikan status HTTP 403
                 ->assertJson([
                     // Perbaikan: Sesuaikan pesan 'message' dengan yang dikembalikan middleware
                     'message' => 'Otorisasi gagal: Anda tidak ter-assign ke Program Studi manapun.',
                 ]);

        // Verifikasi database: memastikan tidak ada mapping yang dibuat
        $this->assertDatabaseMissing('mapping_kelas_matakuliahs', [
            'id_matakuliah' => $matakuliah->id,
            'id_tahun_ajaran' => $tahunAjaran->id,
            'nama_kelas' => 'Y',
        ]);
    }

    /**
     * Test Case: Memastikan Kaprodi gagal melakukan Mapping Kelas MK (Nama kelas sudah terdaftar).
     * HTTP Response status code = 422 (Nama kelas sudah terdaftar)
     *
     * @return void
     */
    public function test_kaprodi_cannot_create_mapping_with_duplicate_class_name(): void
    {
        // 1. Persiapan Data
        $programStudiKaprodi = ProgramStudi::factory()->create(['nama' => 'S1 Teknik Industri']);
        $kaprodiUser = $this->createProgramStudiUser($programStudiKaprodi);

        $matakuliah = Matakuliah::factory()->create([
            'id_pic' => Pic::first()->id,
            'nama_matakuliah' => 'Basis Data',
            'kode_matkul' => 'DB202',
        ]);
        $tahunAjaran = TahunAjaran::factory()->create(['tahun_ajaran' => '2024/2025', 'semester' => 'ganjil']);

        $duplicateClassName = 'Kelas X';

        // Buat mapping kelas yang sudah ada dengan nama yang akan diduplikasi
        MappingKelasMatakuliah::factory()->create([
            'id_matakuliah' => $matakuliah->id,
            'id_tahun_ajaran' => $tahunAjaran->id,
            'id_program_studi' => $programStudiKaprodi->id,
            'nama_kelas' => $duplicateClassName,
            'kuota' => 50,
            'team_teaching' => false,
        ]);

        // Data untuk mapping kelas BARU yang akan ditambahkan dengan nama duplikat
        $requestPayload = [
            'id_matakuliah' => $matakuliah->id,
            'id_tahun_ajaran' => $tahunAjaran->id,
            'classes' => [
                [
                    'nama_kelas' => $duplicateClassName, // Nama kelas yang sama
                    'kuota' => 40,
                    'team_teaching' => false,
                ],
            ],
        ];

        // 2. Aksi: Login sebagai Kaprodi dan kirim POST request untuk menambahkan mapping kelas baru
        $response = $this->actingAs($kaprodiUser, 'sanctum')->postJson('/api/v1/masterdata/mappingkelasmatakuliahs', $requestPayload);

        // 3. Assertions
        $response->assertStatus(422) // Memastikan status HTTP 422 (Unprocessable Entity)
                 ->assertJson([
                     'message' => "Nama kelas '{$duplicateClassName}' sudah terdaftar untuk mata kuliah, tahun ajaran, dan program studi ini.", // Perbaikan: Langsung cocokkan pesan spesifik
                     'errors' => [
                         'classes.0.nama_kelas' => ["Nama kelas '{$duplicateClassName}' sudah terdaftar untuk mata kuliah, tahun ajaran, dan program studi ini."]
                     ]
                 ]);

        // Pastikan tidak ada mapping baru yang dibuat (jumlah tetap 1)
        $this->assertDatabaseCount('mapping_kelas_matakuliahs', 1);
    }

    /**
     * Test Case: Memastikan Kaprodi gagal melakukan Mapping Kelas MK (Nama kelas muncul lebih dari satu kali dalam daftar).
     * HTTP Response status code = 422 (Nama kelas muncul lebih dari satu kali dalam daftar)
     *
     * @return void
     */
    public function test_kaprodi_cannot_create_mapping_with_duplicate_class_name_in_request(): void
    {
        // 1. Persiapan Data
        $programStudiKaprodi = ProgramStudi::factory()->create(['nama' => 'S1 Teknik Lingkungan']);
        $kaprodiUser = $this->createProgramStudiUser($programStudiKaprodi);

        $matakuliah = Matakuliah::factory()->create([
            'id_pic' => Pic::first()->id,
            'nama_matakuliah' => 'Statistika',
            'kode_matkul' => 'ST303',
        ]);
        $tahunAjaran = TahunAjaran::factory()->create(['tahun_ajaran' => '2025/2026', 'semester' => 'ganjil']);

        $duplicateClassNameInRequest = 'Kelas Duplikat';

        // Data untuk mapping kelas yang akan ditambahkan, dengan nama kelas duplikat dalam satu request
        $requestPayload = [
            'id_matakuliah' => $matakuliah->id,
            'id_tahun_ajaran' => $tahunAjaran->id,
            'classes' => [
                [
                    'nama_kelas' => $duplicateClassNameInRequest,
                    'kuota' => 30,
                    'team_teaching' => false,
                ],
                [
                    'nama_kelas' => $duplicateClassNameInRequest, // Nama kelas yang sama dalam request yang sama
                    'kuota' => 35,
                    'team_teaching' => true,
                ],
            ],
        ];

        // 2. Aksi: Login sebagai Kaprodi dan kirim POST request
        $response = $this->actingAs($kaprodiUser, 'sanctum')->postJson('/api/v1/masterdata/mappingkelasmatakuliahs', $requestPayload);

        // 3. Assertions
        $response->assertStatus(422) // Memastikan status HTTP 422 (Unprocessable Entity)
                 ->assertJson([
                     'message' => 'Nama kelas \'Kelas Duplikat\' muncul lebih dari sekali dalam daftar. (and 1 more error)',
                 ]);

        // Pastikan tidak ada mapping yang dibuat
        $this->assertDatabaseCount('mapping_kelas_matakuliahs', 0);
    }


    public function test_kaprodi_cannot_create_mapping_server_erorr(): void
    {
        $unauthorizedUser = User::factory()->create();
        $layananAkademikRole = Role::where('name', 'LayananAkademik')->first();
        User_Role::create([
            'user_id' => $unauthorizedUser->id,
            'role_id' => $layananAkademikRole->id,
            'roleable_type' => null,
            'roleable_id' => null,
        ]);

        $response = $this->actingAs($unauthorizedUser, 'sanctum')->postJson('/api/v1/masterdata/mappingkelasmatakuliahs', $requestPayload);

        // 3. Assertions
        $response->assertStatus(500) // Memastikan status HTTP 403 (Forbidden)
                 ->assertJson([
                     // Perbaikan: Sesuaikan pesan 'message' dengan yang dikembalikan middleware
                     'message' => 'Forbidden: You do not have the required role.',
                 ]);
    }
}
