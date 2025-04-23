<?php

namespace App\Http\Controllers;

use App\Models\Dosen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DosenController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = Dosen::with('kelompokKeahlian')->get();
        return response()->json([
            'success' => true,
            'message' => 'List All Dosen Data',
            'data' => $data
        ]);
    }
    public function getAllDosen()
    {
        $data = Dosen::with('kelompokKeahlian:id,nama')
            ->select('id', 'name', 'lecturer_code', 'nip', 'status_pegawai', 'id_kelompok_keahlian')
            ->get();
        return response()->json([
            'success' => true,
            'message' => 'List All Dosen Data (id, name, lecturer_code, nip, kelompok_keahlian, status_pegawai)',
            'data' => $data
        ]);
    }
    public function getAllDosenByKKId($id_kk)
    {
        $data = Dosen::with('kelompokKeahlian:id,nama')
            ->select('id', 'name', 'lecturer_code', 'nip', 'status_pegawai', 'id_kelompok_keahlian')
            ->where('id_kelompok_keahlian', $id_kk)
            ->get();
        return response()->json([
            'success' => true,
            'message' => 'List All Dosen Data (id, name, lecturer_code, nip, kelompok_keahlian, status_pegawai)',
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
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'lecturer_code' => 'required|string|max:3',
            'email' => 'required|email|unique:dosens,email',
            'jabatan_fungsional_akademik' => 'required|in:Asisten Ahli,Lektor,Lektor Kepala,Guru Besar',
            'status_pegawai' => 'required|in:Dosen Perbantuan Kopertis,Dosen Perbantuan Telkom,Dosen Profesional (full time),Dosen Profesional (part time),Pegawai Tetap',
            'pendidikan_terakhir' => 'required|in:SMA,S-1,S-2,S-3',
            'nidn' => 'nullable|string|max:100',
            'id_kelompok_keahlian' => 'required|exists:kelompok_keahlians,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $dosen = Dosen::create([
            'name' => $request->name,
            'lecturer_code' => $request->lecturer_code,
            'email' => $request->email,
            'jabatan_fungsional_akademik' => $request->jabatan_fungsional_akademik,
            'status_pegawai' => $request->status_pegawai,
            'pendidikan_terakhir' => $request->pendidikan_terakhir,
            'nidn' => $request->nidn,
            'id_kelompok_keahlian' => $request->id_kelompok_keahlian,
        ]);

        return response()->json([
            'message' => 'Data dosen berhasil ditambahkan.',
            'data' => $dosen
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Dosen $dosen)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Dosen $dosen)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Dosen $dosen)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Dosen $dosen)
    {
        //
    }
}
