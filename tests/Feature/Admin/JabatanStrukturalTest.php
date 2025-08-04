<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\User_Role;
use App\Models\JabatanStruktural;
use App\Models\Dosen;
use App\Models\KelompokKeahlian;


class JabatanStrukturalTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['id' => 1, 'name' => 'Superadmin']);
        KelompokKeahlian::firstOrCreate(['id' => 1, 'nama' => 'KK A']);
        KelompokKeahlian::firstOrCreate(['id' => 2, 'nama' => 'KK B']);
        KelompokKeahlian::firstOrCreate(['id' => 3, 'nama' => 'KK C']);
        KelompokKeahlian::firstOrCreate(['id' => 4, 'nama' => 'KK D']);
    }

    protected function createAdminUser()
    {
        $admin = User::factory()->create();
        $adminRole = Role::where('name', 'Superadmin')->first();
        User_Role::create([
            'user_id' => $admin->id,
            'role_id' => $adminRole->id,
            'roleable_type' => null,
            'roleable_id' => null,
        ]);
        return $admin;
    }

    public function test_admin_can_add_jabatan_struktural_successfully(): void
    {

        $admin = $this->createAdminUser();
        $jabatanData = [
            'nama' => $this->faker->unique()->jobTitle,
            'konversi_sks' => $this->faker->numberBetween(1, 10),
        ];

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/masterdata/jabatanstruktural', $jabatanData);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Jabatan struktural berhasil ditambahkan.',
                 ]);

    }

     public function test_admin_can_assign_jabatan_struktural_to_dosen_successfully(): void
    {

        $admin = $this->createAdminUser();
        $dosen = Dosen::factory()->create(['id_jabatan_struktural' => null]);
        $jabatanStruktural = JabatanStruktural::factory()->create();

        $this->assertDatabaseHas('dosens', [
            'id' => $dosen->id,
            'id_jabatan_struktural' => null,
        ]);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/masterdata/assignjabatantodosen', [
            'id_dosen' => $dosen->id,
            'id_jabatan_struktural' => $jabatanStruktural->id,
        ]);


        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Jabatan Struktural berhasil di-assign ke Dosen.',
                 ]);
    }

    public function test_admin_cannot_assign_jabatan_struktural_if_dosen_not_found(): void
    {

        $admin = $this->createAdminUser();
        $jabatanStruktural = JabatanStruktural::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/masterdata/assignjabatantodosen', [
            'id_dosen' => 9999,
            'id_jabatan_struktural' => $jabatanStruktural->id,
        ]);

        // 3. Assertions
        $response->assertStatus(404)
                 ->assertJson([
                 ]);
    }

    public function test_admin_cannot_assign_jabatan_struktural_with_invalid_data(): void
    {

    $admin = $this->createAdminUser();

    $kelompokKeahlian = KelompokKeahlian::firstOrCreate(['id' => 1, 'nama' => 'Default KK for Invalid Test']);
    $dosen = Dosen::factory()->create([
        'id_kelompok_keahlian' => $kelompokKeahlian->id,
    ]);
    $jabatanStruktural = JabatanStruktural::factory()->create();


        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/masterdata/assignjabatantodosen', [
            'id_dosen' => 'abc',
            'id_jabatan_struktural' => $jabatanStruktural->id,
        ]);
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['id_dosen']);
    }

      public function test_admin_assign_jabatan_struktural_server_error(): void
    {
        // 1. Persiapan Data
        $admin = $this->createAdminUser();
        $dosen = Dosen::factory()->create([
            'id_kelompok_keahlian' => KelompokKeahlian::first()->id,
            'id_jabatan_struktural' => JabatanStruktural::first()->id, // Dosen memiliki jabatan struktural
        ]);
        $jabatanStruktural = JabatanStruktural::factory()->create();

        $requestPayload = [
            'id_dosen' => $dosen->id,
            'id_jabatan_struktural' => $jabatanStruktural->id,
        ];

        $response = $this->actingAs($admin, 'sanctum')
                         ->withoutExceptionHandling() // Uncomment ini untuk melihat exception asli
                         ->postJson('/api/v1/masterdata/assignjabatantodosen', $requestPayload);

        // 3. Assertions
        $response->assertStatus(500)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Terjadi kesalahan pada server.',
                 ]);

        // Pastikan tidak ada perubahan yang terjadi di database jika ada error server
        $this->assertDatabaseHas('dosens', [
            'id' => $dosen->id,
            'id_jabatan_struktural' => $dosen->id_jabatan_struktural, // Memastikan jabatan tidak berubah
        ]);
    }


}
