<?php

namespace App\Http\Controllers;

use App\Models\ProgramStudi;
use App\Http\Requests\StoreProgramStudiRequest;
use App\Http\Requests\UpdateProgramStudiRequest;
use Illuminate\Http\Request;

class ProgramStudiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $prodi = ProgramStudi::all();

        return response()->json([
            'status' => 'success',
            'data' => $prodi
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
        try {
            // Melakukan validasi langsung di dalam controller
            $validatedData = $request->validate([
                'nama' => 'required|string|max:255|unique:program_studis,nama',
            ], [
                // Pesan kustom untuk error validasi
                'name.required' => 'Nama program studi wajib diisi.',
                'name.unique' => 'Nama program studi sudah ada.',
            ]);

            // Membuat record baru dari data yang sudah tervalidasi
            $programStudi = ProgramStudi::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Program Studi berhasil ditambahkan.',
                'data' => $programStudi
            ], 201); // HTTP 201 Created

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan Program Studi karena terjadi kesalahan pada server.',
            ], 500); // HTTP 500 Internal Server Error
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(ProgramStudi $programStudi)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProgramStudi $programStudi)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProgramStudiRequest $request, ProgramStudi $programStudi)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProgramStudi $programStudi)
    {
        //
    }
}
