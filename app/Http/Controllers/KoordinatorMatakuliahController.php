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
        $data = KoordinatorMatakuliah::all();
        return response()->json([
            'success' => true,
            'message' => 'Get Data Koordinator Matakuliah',
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
        //
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
