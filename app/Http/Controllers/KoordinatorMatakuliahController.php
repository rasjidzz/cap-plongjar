<?php

namespace App\Http\Controllers;

use App\Models\KoordinatorMatakuliah;
use App\Models\MappingKelasMatakuliah;
use App\Models\ProgramStudi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KoordinatorMatakuliahController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = KoordinatorMatakuliah::with([
            'dosen:id,name,lecturer_code', // Ambil ID, nama, dan kode dosen
            'mappingKelasMatakuliah:id,nama_kelas,id_matakuliah,id_tahun_ajaran', // Ambil info dasar mapping
            'mappingKelasMatakuliah.matakuliah:id,nama_matakuliah,kode_matkul', // Ambil info mata kuliah dari mapping
            'mappingKelasMatakuliah.tahunAjaran:id,tahun_ajaran,semester' // Ambil info tahun ajaran dari mapping
        ])->get();
        return response()->json([
            'success' => true,
            'message' => 'Get All Data Koordinator Matakuliah With Dosen, MappingKelasMatakuliah, Matakuliah, Tahun Ajaran',
            'data' => $data
        ]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'id_dosen' => 'required|exists:dosens,id',
            'id_mapping_kelas_matakuliah' => 'required|exists:mapping_kelas_matakuliahs,id',
        ]);

        $koordinator = KoordinatorMatakuliah::updateOrCreate(
            ['id_mapping_kelas_matakuliah' => $validatedData['id_mapping_kelas_matakuliah']],
            ['id_dosen' => $validatedData['id_dosen']]
        );

        return response()->json([
            'success' => true,
            'message' => 'Koordinator mata kuliah berhasil ditetapkan/diperbarui.',
            'data' => $koordinator->load(['dosen:id,name', 'mappingKelasMatakuliah.matakuliah:id,nama_matakuliah'])
        ], 200);
    }

    // akan mengirimkan array yang berisi id_mapping_kelas_matakuliah per prodi
    // public function store(Request $request)
    // {
    //     $validatedData = $request->validate([
    //         'id_dosen' => 'required|exists:dosens,id',
    //         'id_mapping_kelas_matakuliah' => 'required|exists:mapping_kelas_matakuliahs,id',
    //     ]);
    // }

    public function assignKoordinatorByProgramStudi(Request $request)
    {
        // 1. Validasi input dari request
        $validatedData = $request->validate([
            'id_dosen' => 'required|integer|exists:dosens,id',
            'id_program_studi' => 'required|integer|exists:program_studis,id',
            'id_tahun_ajaran' => 'required|integer|exists:tahun_ajarans,id',
            'id_matakuliah' => 'required|integer|exists:matakuliahs,id'
        ]);

        $id_dosen_koordinator = $validatedData['id_dosen'];
        $id_program_studi_filter = $validatedData['id_program_studi'];
        $id_tahun_ajaran_filter = $validatedData['id_tahun_ajaran'];
        $id_matakuliah_filter = $validatedData['id_matakuliah'];

        $assignedOrUpdatedRecords = [];

        DB::beginTransaction(); // Mulai transaksi database

        try {
            // 2. Ambil semua MappingKelasMatakuliah berdasarkan id_program_studi dan id_tahun_ajaran
            $mappingsToProcess = MappingKelasMatakuliah::where('id_program_studi', $id_program_studi_filter)
                ->where('id_tahun_ajaran', $id_tahun_ajaran_filter)
                ->where('id_matakuliah', $id_matakuliah_filter)
                ->get();

            if ($mappingsToProcess->isEmpty()) {
                DB::rollBack(); // Tidak ada yang diproses, rollback (meskipun tidak ada perubahan)
                return response()->json([
                    'success' => true, // Dianggap sukses karena request valid, hanya saja tidak ada data untuk diubah
                    'message' => 'Tidak ada kelas mata kuliah yang ditemukan untuk Program Studi dan Tahun Ajaran yang dipilih. Tidak ada koordinator yang diassign.',
                    'data' => []
                ], 200);
            }

            // 3. Loop melalui setiap mapping dan assign dosen sebagai koordinator
            foreach ($mappingsToProcess as $mapping) {
                $koordinator = KoordinatorMatakuliah::updateOrCreate(
                    [
                        // Kriteria untuk mencari record yang sudah ada:
                        'id_mapping_kelas_matakuliah' => $mapping->id,
                    ],
                    [
                        // Data yang akan diisi atau diupdate:
                        'id_dosen' => $id_dosen_koordinator,
                    ]
                );
                // Anda bisa memilih untuk tidak me-load relasi di sini untuk respons yang lebih cepat
                // atau load jika Anda ingin mengembalikan detail lengkap
                $assignedOrUpdatedRecords[] = $koordinator->load([
                    'dosen:id,name,lecturer_code',
                    'mappingKelasMatakuliah:id,nama_kelas,id_matakuliah',
                    'mappingKelasMatakuliah.matakuliah:id,nama_matakuliah,kode_matkul'
                ]);
            }

            DB::commit(); // Commit transaksi jika semua berhasil

            return response()->json([
                'success' => true,
                'message' => count($assignedOrUpdatedRecords) . ' kelas mata kuliah untuk Program Studi yang dipilih berhasil di-assign/diperbarui koordinatornya.',
                'data' => $assignedOrUpdatedRecords,
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback transaksi jika terjadi error lain
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan pada server saat mengassign koordinator secara massal berdasarkan program studi.',
            ], 500);
        }
    }
    public function assignKoordinatorByProgramStudiWithLoggedInProdi(Request $request)
    {
        // 1. Validasi input dari request
        $validatedData = $request->validate([
            'id_dosen' => 'required|integer|exists:dosens,id',
            'id_tahun_ajaran' => 'required|integer|exists:tahun_ajarans,id',
            'id_matakuliah' => 'required|integer|exists:matakuliahs,id'
        ]);

        $id_dosen_koordinator = $validatedData['id_dosen'];
        $id_tahun_ajaran_filter = $validatedData['id_tahun_ajaran'];
        $id_matakuliah_filter = $validatedData['id_matakuliah'];

        $assignedOrUpdatedRecords = [];

        DB::beginTransaction(); // Mulai transaksi database

        try {
            $user = $request->user();
            $user->loadMissing('roles');
            $programStudi = null;
            foreach ($user->roles as $role) {
                if ($role->name === 'ProgramStudi' && isset($role->pivot->roleable_id)) {
                    $programStudi = ProgramStudi::find($role->pivot->roleable_id);
                    if ($programStudi) {
                        $id_program_studi_filter = $programStudi->id;
                        break;
                    }
                }
            }
            if (!$programStudi) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak ter-assign ke Program Studi atau Kelompok Keahlian manapun.',
                ], 403);
            }
            // Debug
            // return response()->json([
            //     'success' => true, // Dianggap sukses karena request valid, hanya saja tidak ada data untuk diubah
            //     'message' => 'Masuk Sini',
            //     'data' => [
            //         $programStudi,
            //         $validatedData
            //     ]
            // ], 200);
            // Debug

            // 2. Ambil semua MappingKelasMatakuliah berdasarkan id_program_studi dan id_tahun_ajaran
            $mappingsToProcess = MappingKelasMatakuliah::where('id_program_studi', $id_program_studi_filter)
                ->where('id_tahun_ajaran', $id_tahun_ajaran_filter)
                ->where('id_matakuliah', $id_matakuliah_filter)
                ->get();

            if ($mappingsToProcess->isEmpty()) {
                DB::rollBack(); // Tidak ada yang diproses, rollback (meskipun tidak ada perubahan)
                return response()->json([
                    'success' => true, // Dianggap sukses karena request valid, hanya saja tidak ada data untuk diubah
                    'message' => 'Tidak ada kelas mata kuliah yang ditemukan untuk Program Studi dan Tahun Ajaran yang dipilih. Tidak ada koordinator yang diassign.',
                    'data' => []
                ], 200);
            }

            // 3. Loop melalui setiap mapping dan assign dosen sebagai koordinator
            foreach ($mappingsToProcess as $mapping) {
                $koordinator = KoordinatorMatakuliah::updateOrCreate(
                    [
                        // Kriteria untuk mencari record yang sudah ada:
                        'id_mapping_kelas_matakuliah' => $mapping->id,
                    ],
                    [
                        // Data yang akan diisi atau diupdate:
                        'id_dosen' => $id_dosen_koordinator,
                    ]
                );
                // Anda bisa memilih untuk tidak me-load relasi di sini untuk respons yang lebih cepat
                // atau load jika Anda ingin mengembalikan detail lengkap
                $assignedOrUpdatedRecords[] = $koordinator->load([
                    'dosen:id,name,lecturer_code',
                    'mappingKelasMatakuliah:id,nama_kelas,id_matakuliah',
                    'mappingKelasMatakuliah.matakuliah:id,nama_matakuliah,kode_matkul'
                ]);
            }

            DB::commit(); // Commit transaksi jika semua berhasil

            return response()->json([
                'success' => true,
                'message' => count($assignedOrUpdatedRecords) . ' kelas mata kuliah untuk Program Studi ' . $programStudi->nama . ' berhasil di-assign/diperbarui koordinatornya.',
                'data' => $assignedOrUpdatedRecords,
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback transaksi jika terjadi error lain
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan pada server saat mengassign koordinator secara massal berdasarkan program studi.',
            ], 500);
        }
    }

    public function show(KoordinatorMatakuliah $koordinatorMatakuliah)
    {
        // Muat relasi yang diinginkan ke instance model yang sudah ada.
        $koordinatorMatakuliah->load([
            'dosen:id,name,lecturer_code',
            'mappingKelasMatakuliah:id,nama_kelas,id_matakuliah,id_tahun_ajaran',
            'mappingKelasMatakuliah.matakuliah:id,nama_matakuliah,kode_matkul',
            'mappingKelasMatakuliah.tahunAjaran:id,tahun_ajaran,semester'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Detail Data Koordinator Mata Kuliah Berhasil Dimuat',
            'data' => $koordinatorMatakuliah
        ]);
    }

    public function update(Request $request, KoordinatorMatakuliah $koordinatorMatakuliah)
    {
        //
    }

    public function destroy(KoordinatorMatakuliah $koordinatorMatakuliah)
    {
        //
    }
}
