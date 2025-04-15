<?php

namespace App\Http\Controllers;

use App\Models\Dosen;
use App\Models\Pic;
use Illuminate\Http\Request;

class MasterDataController extends Controller
{
    public function AddMatakuliah(Request $request) {}
    public function AddDosenData(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'lecturer_code' => 'required|string|max:3',
            'email' => 'required|email|unique:dosens,email',
            'jabatan_fungsional_akademik' => 'required|in:Asisten Ahli,Lektor,Lektor Kepala,Guru Besar',
            'status_pegawai' => 'required|in:Dosen Perbantuan Kopertis,Dosen Perbantuan Telkom,Dosen Profesional (full time),Dosen Profesional (part time),Pegawai Tetap',
            'pendidikan_terakhir' => 'required|in:S-1,S-2,S-3',
            'nidn' => 'required|string|max:100',
            'id_kelompok_keahlian' => 'required|exists:kelompok_keahlians,id'
        ]);

        $dosen = Dosen::create([
            'name' => $request->name,
            'lecturer_code' => $request->lecturer_code,
            'email' => $request->email,
            'jabatan_fungsional_akademik' => $request->jabatan_fungsional_akademik,
            'status_pegawai' => $request->status_pegawai,
            'pendidikan_terakhir' => $request->pendidikan_terakhir,
            'nidn' => $request->didn,
            'id_kelompok_keahlian' => $request->id_kelompok_keahlian,
        ]);

        if (!$dosen) {
            return response()->json(['message' => 'Failed to create new dosen'], 400);
        }

        return response()->json([
            'message' => 'Lecturer Data Added Successfully'
        ], 201);
    }
    public function AddPic(Request $request)
    {
        $request->validate([
            'nama_pic' => 'required|max:255'
        ]);

        Pic::create([
            'name' => $request['nama_pic']
        ]);

        return response()->json([
            'message' => 'PIC Added Successfully'
        ], 201);
    }
    public function getAllPic()
    {
        return Pic::all();
    }
}
