<?php

namespace Tests\Feature\Kaprodi;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\User_Role;
use App\Models\Matakuliah;
use App\Models\ProgramStudi;
use App\Models\Pic;
use App\Models\TahunAjaran;
use App\Models\KelompokKeahlian;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class MatakuliahTest extends TestCase
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
        TahunAjaran::firstOrCreate(['id' => 1, 'tahun_ajaran' => '2024/2025', 'semester' => 'ganjil']);
        KelompokKeahlian::firstOrCreate(['id' => 1, 'nama' => 'Default KK']);
    }

    protected function createProgramStudiUser(ProgramStudi $programStudi)
    {
        $user = User::factory()->create([
            'email' => $this->faker->unique()->safeEmail,
            'password' => Hash::make('password'),
        ]);
        $prodiRole = Role::where('name', 'ProgramStudi')->first();
        User_Role::create([
            'user_id' => $user->id,
            'role_id' => $prodiRole->id,
            'roleable_type' => ProgramStudi::class,
            'roleable_id' => $programStudi->id,
        ]);
        return $user;
    }

    public function test_kaprodi_can_create_new_matakuliah_successfully(): void
    {
        $programStudiKaprodi = ProgramStudi::factory()->create(['nama' => 'S1 Data Sains']);
        $kaprodiUser = $this->createProgramStudiUser($programStudiKaprodi);
        $pic = Pic::first();

            $matakuliahData = [
            'id_pic' => $pic->id,
            'nama_matakuliah' => 'Mata Kuliah Baru Dibuat', // Nama unik untuk test ini
            'kode_matkul' => 'NEWMK', // Kode unik untuk test ini
            'sks' => 3,
            'praktikum' => false,
            'mandatory_status' => 'pilihan',
            'mode_perkuliahan' => 'online',
            'matakuliah_eksepsi' => 'tidak',
            'tingkat_matakuliah' => 'Tingkat 1',
            'hour_target' => 48, // Pastikan hour_target ada
        ];

        $response = $this->actingAs($kaprodiUser, 'sanctum')
                         ->postJson('/api/v1/masterdata/matakuliahs', $matakuliahData);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Matakuliah berhasil ditambahkan.',
                 ]);

    }

    /**
     * Test Case: Memastikan Kaprodi gagal membuat MK dengan data tidak valid.
     * HTTP Response status code = 422 (Validasi Gagal)
     *
     * @return void
     */
    public function test_kaprodi_cannot_create_matakuliah_with_invalid_data(): void
    {
        // 1. Persiapan Data
        $programStudiKaprodi = ProgramStudi::factory()->create(['nama' => 'S1 Informatika']);
        $kaprodiUser = $this->createProgramStudiUser($programStudiKaprodi);
        $pic = Pic::first();

        // Skenario 1: nama_matakuliah kosong (required)
        $response1 = $this->actingAs($kaprodiUser, 'sanctum')->postJson('/api/v1/masterdata/matakuliahs', [
            'nama_matakuliah' => '',
            'kode_matkul' => 'KODE1', 'sks' => 3, 'praktikum' => false, 'id_pic' => $pic->id,
            'mandatory_status' => 'wajib_prodi', 'mode_perkuliahan' => 'online',
            'matakuliah_eksepsi' => 'ya', 'tingkat_matakuliah' => 'Tingkat 1',
        ]);
        $response1->assertStatus(422)->assertJsonValidationErrors(['nama_matakuliah']);
    }

    public function test_kaprodi_can_view_all_matakuliahs_paginated_successfully(): void
    {
        // 1. Persiapan Data
        $programStudi = ProgramStudi::first();
        $kaprodiUser = $this->createProgramStudiUser($programStudi);
        $pic = Pic::first();
        Matakuliah::factory()->count(15)->create(['id_pic' => $pic->id]); // Buat 15 mata kuliah

        // 2. Aksi: Login sebagai Kaprodi dan kirim GET request tanpa parameter pencarian
        // Endpoint: /api/v1/masterdata/matakuliahs
        $response = $this->actingAs($kaprodiUser, 'sanctum')->getJson('/api/v1/masterdata/matakuliahs');

        // 3. Assertions
        $response->assertStatus(200) // Memastikan status HTTP 200 (Success)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Daftar Mata Kuliah berhasil dimuat.', // Pesan dari MatakuliahController@index
                 ]);

    }

     public function test_kaprodi_can_update_existing_matakuliah_successfully(): void
    {
        // 1. Persiapan Data
        $programStudiKaprodi = ProgramStudi::factory()->create(['nama' => 'S1 Data Sains']);
        $kaprodiUser = $this->createProgramStudiUser($programStudiKaprodi);
        $pic = Pic::first();

        // Buat mata kuliah yang akan diupdate
        $matakuliahToUpdate = Matakuliah::factory()->create([
            'id_pic' => $pic->id,
            'nama_matakuliah' => 'Mata Kuliah Lama',
            'kode_matkul' => 'LAMA1',
            'sks' => 3,
            'praktikum' => false,
            'mandatory_status' => 'pilihan',
            'mode_perkuliahan' => 'online',
            'matakuliah_eksepsi' => 'tidak',
            'tingkat_matakuliah' => 'Tingkat 1',
            'hour_target' => 48,
        ]);

        $updatedData = [
            'nama_matakuliah' => 'Mata Kuliah Baru',
            'kode_matkul' => 'BARU2', // Kode unik baru
            'sks' => 4, // SKS diubah
            'praktikum' => true, // Praktikum diubah
            'id_pic' => $pic->id,
            'mandatory_status' => 'wajib_prodi',
            'mode_perkuliahan' => 'hybrid',
            'matakuliah_eksepsi' => 'ya',
            'tingkat_matakuliah' => 'Tingkat 2',
            // hour_target akan dihitung ulang oleh controller
        ];

        // 2. Aksi: Login sebagai Kaprodi dan kirim PUT request untuk update mata kuliah
        // Endpoint: /api/v1/masterdata/matakuliahs/{matakuliah}
        $response = $this->actingAs($kaprodiUser, 'sanctum')->putJson('/api/v1/masterdata/matakuliahs/' . $matakuliahToUpdate->id, $updatedData);

        // 3. Assertions
        $response->assertStatus(200) // Memastikan status HTTP 200 (Success)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Mata kuliah berhasil diperbarui.',
                 ]);

    }

    public function test_kaprodi_can_soft_delete_matakuliah_successfully(): void
    {
        // 1. Persiapan Data
        $programStudiKaprodi = ProgramStudi::factory()->create(['nama' => 'S1 Teknik Kimia']);
        $kaprodiUser = $this->createProgramStudiUser($programStudiKaprodi);
        $pic = Pic::first();

        // Buat mata kuliah yang akan dihapus
        $matakuliahToDelete = Matakuliah::factory()->create([
            'id_pic' => $pic->id,
            'nama_matakuliah' => 'Mata Kuliah untuk Dihapus',
            'kode_matkul' => 'DEL1',
            'sks' => 3,
            'praktikum' => false,
            'mandatory_status' => 'pilihan',
            'mode_perkuliahan' => 'online',
            'matakuliah_eksepsi' => 'tidak',
            'tingkat_matakuliah' => 'Tingkat 1',
            'hour_target' => 48,
        ]);

        // Pastikan mata kuliah ada di database dan belum terhapus
        $this->assertDatabaseHas('matakuliahs', [
            'id' => $matakuliahToDelete->id,
            'deleted_at' => null,
        ]);

        // 2. Aksi: Login sebagai Kaprodi dan kirim DELETE request
        // Endpoint: /api/v1/masterdata/matakuliahs/{matakuliah}
        $response = $this->actingAs($kaprodiUser, 'sanctum')->deleteJson('/api/v1/masterdata/matakuliahs/' . $matakuliahToDelete->id, [
            'password' => 'password', // Password user Kaprodi untuk otentikasi delete
        ]);

        // 3. Assertions
        $response->assertStatus(200) // Memastikan status HTTP 200 (Success)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Mata kuliah berhasil dihapus (soft delete).',
                 ]);

        // Verifikasi database: memastikan kolom 'deleted_at' sudah terisi
        $this->assertSoftDeleted('matakuliahs', [
            'id' => $matakuliahToDelete->id,
        ]);
    }

    public function test_kaprodi_cannot_soft_delete_matakuliah_with_incorrect_password(): void
    {
        // 1. Persiapan Data
        $programStudiKaprodi = ProgramStudi::factory()->create(['nama' => 'S1 Teknik Fisika']);
        $kaprodiUser = $this->createProgramStudiUser($programStudiKaprodi); // Password user ini adalah 'password'
        $pic = Pic::first();

        $matakuliahToDelete = Matakuliah::factory()->create([
            'id_pic' => $pic->id,
            'nama_matakuliah' => 'Mata Kuliah Tidak Dihapus',
            'kode_matkul' => 'NODEL1',
            'sks' => 3,
            'praktikum' => false,
            'mandatory_status' => 'pilihan',
            'mode_perkuliahan' => 'online',
            'matakuliah_eksepsi' => 'tidak',
            'tingkat_matakuliah' => 'Tingkat 1',
            'hour_target' => 48,
        ]);

        // Pastikan mata kuliah ada di database dan belum terhapus
        $this->assertDatabaseHas('matakuliahs', [
            'id' => $matakuliahToDelete->id,
            'deleted_at' => null,
        ]);

        // 2. Aksi: Login sebagai Kaprodi dan kirim DELETE request dengan password salah
        $response = $this->actingAs($kaprodiUser, 'sanctum')->deleteJson('/api/v1/masterdata/matakuliahs/' . $matakuliahToDelete->id, [
            'password' => 'wrong-password', // Password salah
        ]);

        // 3. Assertions
        $response->assertStatus(422) // Memastikan status HTTP 422
                 ->assertJson([
                     'success' => false,
                     'message' => 'Incorrect Password, action denied',
                     'errors' => [
                         'password' => ['Incorrect Password']
                     ]
                 ]);

        // Verifikasi database: memastikan mata kuliah TIDAK dihapus
        $this->assertDatabaseHas('matakuliahs', [
            'id' => $matakuliahToDelete->id,
            'deleted_at' => null, // Masih belum terhapus
        ]);
    }


}
