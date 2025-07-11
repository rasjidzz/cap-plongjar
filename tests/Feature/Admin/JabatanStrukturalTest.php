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
                     'data' => [
                         'nama' => $jabatanData['nama'],
                         'konversi_sks' => $jabatanData['konversi_sks'],
                     ]
                 ]);

        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'nama',
                'konversi_sks',
                'created_at',
                'updated_at',
            ]
        ]);

        $this->assertDatabaseHas('jabatan_strukturals', [
            'nama' => $jabatanData['nama'],
            'konversi_sks' => $jabatanData['konversi_sks'],
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

        $this->assertDatabaseHas('dosens', [
            'id' => $dosen->id,
            'id_jabatan_struktural' => $jabatanStruktural->id,
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
        $response->assertStatus(422)
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


        $response1 = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/masterdata/assignjabatantodosen', [
            'id_dosen' => 'abc', // Bukan integer
            'id_jabatan_struktural' => $jabatanStruktural->id,
        ]);
        $response1->assertStatus(422)
                  ->assertJsonValidationErrors(['id_dosen']);
    }


}
