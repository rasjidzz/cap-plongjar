<?php

namespace App\Http\Controllers;

use App\Models\TahunAjaran;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TahunAjaranController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = TahunAjaran::all();
        return response()->json([
            'success' => true,
            'message' => 'List All Tahun Ajaran',
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
            'tahun_ajaran' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tahun_ajarans')->where(function ($query) use ($request) {
                    return $query->where('semester', $request->semester);
                }),
            ],
            'semester' => ['required', Rule::in(['ganjil', 'genap'])],
        ]);


        $tahunAjaran = TahunAjaran::create([
            'tahun_ajaran' => $validated['tahun_ajaran'],
            'semester' => $validated['semester'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tahun Ajaran berhasil ditambahkan.',
            'data' => $tahunAjaran
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(TahunAjaran $tahunAjaran)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TahunAjaran $tahunAjaran)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TahunAjaran $tahunAjaran)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TahunAjaran $tahunAjaran)
    {
        //
    }
}
