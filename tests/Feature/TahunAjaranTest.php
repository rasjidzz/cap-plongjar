<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\User_Role;
use App\Models\TahunAjaran;
use Illuminate\Support\Facades\DB;

class TahunAjaranTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['id' => 1, 'name' => 'Superadmin']);
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

    public function test_admin_can_create_tahun_ajaran_successfully(): void
    {
        $admin = $this->createAdminUser();
        $tahunAjaranData = [
            'tahun_ajaran' => '2024/2025',
            'semester' => 'ganjil',
        ];

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/masterdata/tahunajarans', $tahunAjaranData);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Tahun Ajaran berhasil ditambahkan.',
                     'data' => [
                         'tahun_ajaran' => $tahunAjaranData['tahun_ajaran'],
                         'semester' => $tahunAjaranData['semester'],
                     ]
                 ]);

        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'tahun_ajaran',
                'semester',
                'created_at',
                'updated_at',
            ]
        ]);

        $this->assertDatabaseHas('tahun_ajarans', [
            'tahun_ajaran' => $tahunAjaranData['tahun_ajaran'],
            'semester' => $tahunAjaranData['semester'],
        ]);
    }

    public function test_admin_cannot_create_duplicate_tahun_ajaran_and_semester(): void
    {
        $admin = $this->createAdminUser();
        $existingTahunAjaran = [
            'tahun_ajaran' => '2023/2024',
            'semester' => 'genap',
        ];
        TahunAjaran::create($existingTahunAjaran);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/masterdata/tahunajarans', $existingTahunAjaran);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['tahun_ajaran']);

        $this->assertDatabaseCount('tahun_ajarans', 1);
    }

    public function test_admin_cannot_create_tahun_ajaran_with_invalid_data(): void
    {
        $admin = $this->createAdminUser();

        $response1 = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/masterdata/tahunajarans', [
            'tahun_ajaran' => '',
            'semester' => 'ganjil',
        ]);
        $response1->assertStatus(422)
                  ->assertJsonValidationErrors(['tahun_ajaran']);

        $response2 = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/masterdata/tahunajarans', [
            'tahun_ajaran' => '2025/2026',
            'semester' => '',
        ]);
        $response2->assertStatus(422)
                  ->assertJsonValidationErrors(['semester']);

        $response3 = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/masterdata/tahunajarans', [
            'tahun_ajaran' => '2025/2026',
            'semester' => 'invalid_semester',
        ]);
        $response3->assertStatus(422)
                  ->assertJsonValidationErrors(['semester']);
    }

    public function test_admin_can_set_active_tahun_ajaran_successfully(): void
    {
        $admin = $this->createAdminUser();
        $tahunAjaran1 = TahunAjaran::factory()->create(['tahun_ajaran' => '2023/2024', 'semester' => 'ganjil', 'status' => false]);
        $tahunAjaran2 = TahunAjaran::factory()->create(['tahun_ajaran' => '2023/2024', 'semester' => 'genap', 'status' => true]);
        $tahunAjaranToActivate = TahunAjaran::factory()->create(['tahun_ajaran' => '2024/2025', 'semester' => 'ganjil', 'status' => false]);

        $this->assertDatabaseHas('tahun_ajarans', ['id' => $tahunAjaran1->id, 'status' => false]);
        $this->assertDatabaseHas('tahun_ajarans', ['id' => $tahunAjaran2->id, 'status' => true]);
        $this->assertDatabaseHas('tahun_ajarans', ['id' => $tahunAjaranToActivate->id, 'status' => false]);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/masterdata/tahun-ajaran/' . $tahunAjaranToActivate->id . '/set-active'); // <-- Perbaikan di sini

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Status Tahun Ajaran berhasil diperbarui. ' . $tahunAjaranToActivate->tahun_ajaran . ' (' . $tahunAjaranToActivate->semester . ') sekarang aktif.',
                     'data' => [
                         'id' => $tahunAjaranToActivate->id,
                         'status' => true,
                     ]
                 ]);

        $this->assertDatabaseHas('tahun_ajarans', ['id' => $tahunAjaranToActivate->id, 'status' => true]);
        $this->assertDatabaseHas('tahun_ajarans', ['id' => $tahunAjaran1->id, 'status' => false]);
        $this->assertDatabaseHas('tahun_ajarans', ['id' => $tahunAjaran2->id, 'status' => false]);
    }

    public function test_admin_cannot_set_active_tahun_ajaran_if_not_found(): void
    {
        $admin = $this->createAdminUser();
        $nonExistentId = 9999;

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/masterdata/tahun-ajaran/' . $nonExistentId . '/set-active'); // <-- Perbaikan di sini

        $response->assertStatus(404)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Tahun Ajaran dengan ID ' . $nonExistentId . ' tidak ditemukan.',
                 ]);

        $this->assertDatabaseMissing('tahun_ajarans', ['id' => $nonExistentId]);
    }
}
