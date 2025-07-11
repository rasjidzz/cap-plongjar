<?php

namespace Tests\Feature\LAA; // Perhatikan namespace ini

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\User_Role;
use App\Models\ProgramStudi;
use App\Models\TahunAjaran;
use App\Models\Dosen; // Dibutuhkan oleh factories
use App\Models\KelompokKeahlian; // Dibutuhkan oleh factories
use App\Models\JabatanStruktural; // Dibutuhkan oleh factories
use App\Models\Pic; // Dibutuhkan oleh factories
use App\Models\Matakuliah; // Dibutuhkan oleh factories
use App\Models\MappingKelasMatakuliah; // Dibutuhkan oleh factories
use App\Models\PlottinganPengajaran; // Dibutuhkan oleh factories
use Maatwebsite\Excel\Facades\Excel; // Import Facade Excel
use App\Exports\HasilPlottinganExport; // Import class Export yang digunakan
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException; // Untuk assert abort(404)

class ExportHasilTest extends TestCase
{
    use RefreshDatabase; // Memastikan database bersih untuk setiap test
    use WithFaker;     // Untuk membuat data dummy

    /**
     * Setup the test environment.
     * Membuat data dasar yang dibutuhkan sebelum setiap test dijalankan.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Buat role dasar yang dibutuhkan
        Role::firstOrCreate(['id' => 1, 'name' => 'Superadmin']);
        Role::firstOrCreate(['id' => 2, 'name' => 'ProgramStudi']);
        Role::firstOrCreate(['id' => 3, 'name' => 'KelompokKeahlian']);
        Role::firstOrCreate(['id' => 4, 'name' => 'LayananAkademik']); // Role Layanan Akademik
        Role::firstOrCreate(['id' => 5, 'name' => 'KepalaUrusanLab']);

        for ($i = 1; $i <= 10; $i++) {
            KelompokKeahlian::firstOrCreate(['id' => $i, 'nama' => 'KK ' . chr(64 + $i)]);
            JabatanStruktural::firstOrCreate(['id' => $i, 'nama' => 'Jabatan ' . $i, 'konversi_sks' => $i]);
        }
        Pic::firstOrCreate(['id' => 1, 'name' => 'Default PIC']);
        ProgramStudi::firstOrCreate(['id' => 1, 'nama' => 'S1 Informatika']);
        TahunAjaran::firstOrCreate(['id' => 1, 'tahun_ajaran' => '2024/2025', 'semester' => 'ganjil']);
    }

    /**
     * Helper method untuk membuat user Layanan Akademik (LAA).
     *
     * @return \App\Models\User
     */
   protected function createLAAUser()
{
    $user = User::factory()->create();

    $laaRole = Role::firstOrCreate(['name' => 'LayananAkademik']); // Pastikan role selalu tersedia

    User_Role::create([
        'user_id' => $user->id,
        'role_id' => $laaRole->id,
        'roleable_type' => null,
        'roleable_id' => null,
    ]);

    return $user;
}


    /**
     * Test Case: Memastikan LAA dapat mengekspor hasil plottingan pengajaran ke Excel.
     * HTTP Response status code = 200 (Success)
     *
     * @return void
     */
    public function test_laa_can_export_plotting_results_successfully(): void
    {
        // Mock the Excel facade to prevent actual file creation
        Excel::fake();

        // 1. Persiapan Data
        $laaUser = $this->createLAAUser();
        $tahunAjaran = TahunAjaran::factory()->create(['tahun_ajaran' => '2023/2024', 'semester' => 'genap']);
        $programStudi = ProgramStudi::factory()->create(['nama' => 'S1 Teknik Informatika']);

        // Buat data plottingan yang relevan agar ekspor tidak kosong
        $dosen = Dosen::factory()->create(['id_kelompok_keahlian' => KelompokKeahlian::first()->id]);
        $matakuliah = Matakuliah::factory()->create(['id_pic' => Pic::first()->id]);
        $mapping = MappingKelasMatakuliah::factory()->create([
            'id_matakuliah' => $matakuliah->id,
            'id_tahun_ajaran' => $tahunAjaran->id,
            'id_program_studi' => $programStudi->id,
        ]);
        PlottinganPengajaran::create([
            'id_dosen' => $dosen->id,
            'id_mapping_kelas_matakuliah' => $mapping->id,
            'beban_sks' => 3,
        ]);

        // 2. Aksi: Login sebagai LAA dan kirim GET request ke endpoint export
        // Endpoint: /api/v1/plottingan-pengajaran/export/tahun-ajaran/{id_tahun_ajaran}/program-studi/{id_program_studi}
        $response = $this->actingAs($laaUser, 'sanctum')->get(
            '/api/v1/plottingan-pengajaran/export/tahun-ajaran/' . $tahunAjaran->id . '/program-studi/' . $programStudi->id
        );

        // 3. Assertions
        $response->assertStatus(200); // Export biasanya mengembalikan 200 OK

        // Assert that the download method was called with the correct export class and file name
        $fileName = 'hasil_plottingan_prodi_'
            . preg_replace('/\s+/', '_', $programStudi->nama) . '_'
            . str_replace('/', '-', $tahunAjaran->tahun_ajaran) . '_'
            . $tahunAjaran->semester . '.xlsx';

        Excel::assertDownloaded($fileName, function(HasilPlottinganExport $export) use ($tahunAjaran, $programStudi) {
            return $export->idTahunAjaran === $tahunAjaran->id && $export->idProgramStudi === $programStudi->id;
        });
    }

    /**
     * Test Case: Memastikan LAA gagal mengekspor jika Tahun Ajaran tidak ditemukan.
     * HTTP Response status code = 404 (Not Found)
     *
     * @return void
     */
    public function test_laa_cannot_export_if_tahun_ajaran_not_found(): void
    {
        Excel::fake(); // Mock Excel

        // 1. Persiapan Data
        $laaUser = $this->createLAAUser();
        $programStudi = ProgramStudi::factory()->create(['nama' => 'S1 Teknik Industri']);
        $nonExistentTahunAjaranId = 9999;

        // 2. Aksi: Login sebagai LAA dan kirim GET request dengan ID Tahun Ajaran yang tidak ada
        // Menggunakan withoutExceptionHandling() untuk menangkap abort(404)
        $response = $this->actingAs($laaUser, 'sanctum')
                         ->withoutExceptionHandling()
                         ->get('/api/v1/plottingan-pengajaran/export/tahun-ajaran/' . $nonExistentTahunAjaranId . '/program-studi/' . $programStudi->id);

        // 3. Assertions: Memastikan NotFoundHttpException dilemparkan
        $response->assertStatus(404);
        $this->assertInstanceOf(NotFoundHttpException::class, $response->exception);
        $this->assertEquals('Tahun ajaran atau program studi tidak ditemukan.', $response->exception->getMessage());
    }

    /**
     * Test Case: Memastikan LAA gagal mengekspor jika Program Studi tidak ditemukan.
     * HTTP Response status code = 404 (Not Found)
     *
     * @return void
     */
    public function test_laa_cannot_export_if_program_studi_not_found(): void
    {
        Excel::fake(); // Mock Excel

        // 1. Persiapan Data
        $laaUser = $this->createLAAUser();
        $tahunAjaran = TahunAjaran::factory()->create(['tahun_ajaran' => '2024/2025', 'semester' => 'ganjil']);
        $nonExistentProgramStudiId = 8888;

        // 2. Aksi: Login sebagai LAA dan kirim GET request dengan ID Program Studi yang tidak ada
        // Menggunakan withoutExceptionHandling() untuk menangkap abort(404)
        $response = $this->actingAs($laaUser, 'sanctum')
                         ->withoutExceptionHandling()
                         ->get('/api/v1/plottingan-pengajaran/export/tahun-ajaran/' . $tahunAjaran->id . '/program-studi/' . $nonExistentProgramStudiId);

        // 3. Assertions: Memastikan NotFoundHttpException dilemparkan
        $response->assertStatus(404);
        $this->assertInstanceOf(NotFoundHttpException::class, $response->exception);
        $this->assertEquals('Tahun ajaran atau program studi tidak ditemukan.', $response->exception->getMessage());
    }
}
