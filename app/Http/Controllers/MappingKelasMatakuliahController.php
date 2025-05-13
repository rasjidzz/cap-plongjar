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
