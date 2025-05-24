<?php

namespace App\Http\Controllers;

use App\Models\JabatanStruktural;
use App\Http\Requests\StoreJabatanStrukturalRequest;
use App\Http\Requests\UpdateJabatanStrukturalRequest;
use Illuminate\Http\Request;

class JabatanStrukturalController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = JabatanStruktural::all();

        return response()->json([
            'success' => true,
            'message' => 'List All Jabatan Struktural',
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
            'nama' => 'required|string|max:255|unique:jabatan_strukturals,nama',
            'konversi_sks' => 'required|numeric|min:0',
        ]);

        $jabatan = JabatanStruktural::create([
            'nama' => $validated['nama'],
            'konversi_sks' => $validated['konversi_sks'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Jabatan struktural berhasil ditambahkan.',
            'data' => $jabatan
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(JabatanStruktural $jabatanStruktural)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(JabatanStruktural $jabatanStruktural)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateJabatanStrukturalRequest $request, JabatanStruktural $jabatanStruktural)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(JabatanStruktural $jabatanStruktural)
    {
        //
    }
}
