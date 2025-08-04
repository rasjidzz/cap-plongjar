<?php

namespace Tests\Feature\Admin;

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

    public function test_admin_set_active_tahun_ajaran_server_error(): void
    {
        // 1. Persiapan Data
        $admin = $this->createAdminUser();
        $tahunAjaranToActivate = TahunAjaran::factory()->create(['tahun_ajaran' => '2025/2026', 'semester' => 'ganjil']);

        $response = $this->actingAs($admin, 'sanctum')
                         ->withoutExceptionHandling()
                         ->putJson('/api/v1/masterdata/tahun-ajaran/' . $tahunAjaranToActivate->id . '/set-active');

        // 3. Assertions
        $response->assertStatus(500)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Terjadi kesalahan pada server saat memperbarui status tahun ajaran.',
                 ]);
    }
}
