<?php

namespace App\Http\Controllers;

use App\Models\KoordinatorMatakuliah;
use Illuminate\Http\Request;

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
