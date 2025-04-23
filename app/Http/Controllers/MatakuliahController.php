<?php

namespace App\Http\Controllers;

use App\Models\Matakuliah;
use Illuminate\Http\Request;

class MatakuliahController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = Matakuliah::with('pic')->get();
        return response()->json([
            'success' => true,
            'message' => 'List All Matakuliah',
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
            'kode_matkul' => 'required|string|max:10|unique:matakuliahs,kode_matkul',
            'sks' => 'required|integer|min:1|max:6',
            'praktikum' => 'required|boolean',
            'id_pic' => 'required|exists:pics,id',
            'mandatory_status' => 'required|in:wajib_prodi,pilihan',
            'mode_perkuliahan' => 'required|in:online,onsite,hybrid',
        ]);



        $matakuliah = Matakuliah::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Matakuliah berhasil ditambahkan.',
            'data' => $matakuliah->load('pic')
        ], 201);
    }


    /**
     * Display the specified resource.
     */
    public function show(Matakuliah $matakuliah)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Matakuliah $matakuliah)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Matakuliah $matakuliah)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Matakuliah $matakuliah)
    {
        //
    }
}
