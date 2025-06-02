<?php

namespace App\Http\Controllers;

use App\Models\MappingKelasMatakuliah;
use Illuminate\Http\Request;

class MappingKelasMatakuliahController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = MappingKelasMatakuliah::with(['matakuliah', 'tahunAjaran'])->get();
        return response()->json([
            'success' => true,
            'message' => 'List Mapping Kelas Matakuliah',
            'data' => $data
        ]);
    }

    public function getMappingKelasMatkulbyIdMatkul($id_matakuliah)
    {
        $data = MappingKelasMatakuliah::with(['matakuliah', 'tahunAjaran'])
            ->where('id_matakuliah', $id_matakuliah)
            ->get();

        if ($data->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Mapping kelas matakuliah tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    public function getMappingKelasMatkulByIdMatkulandIdTahunAjaran($id_matakuliah, $id_tahunajaran)
    {
        $data = MappingKelasMatakuliah::with([
            'matakuliah:id,kode_matkul,nama_matakuliah,sks', // Memuat detail mata kuliah
            'tahunAjaran:id,tahun_ajaran,semester',         // Memuat detail tahun ajaran
            'plottinganPengajarans:id,id_mapping_kelas_matakuliah,id_dosen', // Memuat plottingan
            'plottinganPengajarans.dosen:id,name,lecturer_code' // Memuat dosen dari plottingan
        ])
            ->where('id_matakuliah', $id_matakuliah)
            ->where('id_tahun_ajaran', $id_tahunajaran)
            ->orderBy('nama_kelas', 'asc') // Urutkan berdasarkan nama kelas
            ->get();

        if ($data->isEmpty()) {
            return response()->json([
                'success' => true, // Atau false jika Anda menganggap tidak ada data sebagai "error"
                'message' => 'Tidak ada data mapping kelas mata kuliah yang ditemukan untuk kriteria yang diberikan.',
                'data' => []
            ], 200); // atau 404 jika Anda ingin mengindikasikan resource tidak ditemukan
        }

        return response()->json([
            'success' => true,
            'message' => 'Data mapping kelas mata kuliah berhasil dimuat.',
            'data' => $data
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_matakuliah' => 'required|exists:matakuliahs,id',
            'id_tahun_ajaran' => 'required|exists:tahun_ajarans,id',
            'nama_kelas' => 'required|string|max:255',
            'kuota' => 'required|integer|min:1',
            'team_teaching' => 'required|boolean',
        ]);

        $mapping = MappingKelasMatakuliah::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Mapping kelas matakuliah berhasil ditambahkan.',
            'data' => $mapping
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $data = MappingKelasMatakuliah::with(['matakuliah', 'tahunAjaran'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Detail Mapping Kelas Matakuliah',
            'data' => $data
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MappingKelasMatakuliah $mappingKelasMatakuliah)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $mapping = MappingKelasMatakuliah::findOrFail($id);

        $validated = $request->validate([
            'id_matakuliah' => 'sometimes|exists:matakuliahs,id',
            'id_tahun_ajaran' => 'sometimes|exists:tahun_ajarans,id',
            'nama_kelas' => 'sometimes|string|max:255',
            'kuota' => 'sometimes|integer|min:1',
            'team_teaching' => 'sometimes|boolean',
        ]);

        $mapping->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Mapping kelas matakuliah berhasil diperbarui.',
            'data' => $mapping
        ]);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MappingKelasMatakuliah $mappingKelasMatakuliah)
    {
        //
    }
}
