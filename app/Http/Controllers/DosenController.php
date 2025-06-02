<?php

namespace App\Http\Controllers;

use App\Models\Dosen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
// use App\Http\Controllers\Log;

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
    public function getAllDosen(Request $request)
    {
        $query = Dosen::with('kelompokKeahlian:id,nama')
            ->select('id', 'name', 'lecturer_code', 'nip', 'status_pegawai', 'id_kelompok_keahlian')
            ->when($request->search, function ($q) use ($request) {
                $q->where(function ($subQuery) use ($request) {
                    $subQuery->where('name', 'like', '%' . $request->search . '%')
                        ->orWhere('lecturer_code', 'like', '%' . $request->search . '%')
                        ->orWhere('nip', 'like', '%' . $request->search . '%');
                });
            });

        // Default pagination: 10 per page, bisa dicustom lewat query param ?per_page=
        $data = $query->paginate($request->get('per_page', 10));

        return response()->json([
            'success' => true,
            'message' => 'List All Dosen (filtered & paginated)',
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
    // public function getDosenDetailData($id_dosen)
    // {
    //     $data = Dosen::with('kelompokKeahlian:id,nama')
    //         ->select('id', 'name', 'lecturer_code', 'nip', 'status_pegawai', 'id_kelompok_keahlian')
    //         ->where('id', $id_dosen)
    //         ->get();
    //     return response()->json([
    //         'success' => true,
    //         'message' => 'List All Dosen Data (id, name, lecturer_code, nip, kelompok_keahlian, status_pegawai)',
    //         'data' => $data
    //     ]);
    // }

    // public function getDosenDetailData($id_dosen)
    // {
    //     $dosen = Dosen::with('kelompokKeahlian:id,nama')
    //         ->select(
    //             'id',
    //             'name',
    //             'lecturer_code',
    //             'id_jabatan_struktural',
    //             'nip',
    //             'nidn',
    //             'id_kelompok_keahlian',
    //             'status_pegawai',
    //             'email',
    //             'jabatan_fungsional_akademik',
    //             'pendidikan_terakhir'
    //         )
    //         ->where('id', $id_dosen)
    //         ->first();

    //     if (!$dosen) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Dosen tidak ditemukan.',
    //             'data' => null
    //         ], 404);
    //     }

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Detail Data Dosen',
    //         'data' => [
    //             'nama_dosen' => $dosen->name,
    //             'kode_dosen' => $dosen->lecturer_code,
    //             'jabatan' => $dosen->id_jabatan_struktural,
    //             'home_base' => null,
    //             'nip' => $dosen->nip,
    //             'nidn' => $dosen->nidn,
    //             'bidang_keahlian' => $dosen->kelompokKeahlian?->nama,
    //             'status' => $dosen->status_pegawai,
    //             'contact_person' => $dosen->email,
    //             'jfa' => $dosen->jabatan_fungsional_akademik,
    //             'riwayat_pengajaran' => url("/riwayat-mengajar/{$dosen->id}"),
    //             'pendidikan' => $dosen->pendidikan_terakhir,
    //         ]
    //     ]);
    // }
    public function getDosenDetailData($id_dosen)
    {
        $dosen = Dosen::with([
            'kelompokKeahlian:id,nama',
            'jabatanStruktural:id,nama'
        ])
            ->select(
                'id',
                'name',
                'lecturer_code',
                'id_jabatan_struktural',
                'nip',
                'nidn',
                'id_kelompok_keahlian',
                'status_pegawai',
                'email',
                'jabatan_fungsional_akademik',
                'pendidikan_terakhir'
            )
            ->where('id', $id_dosen)
            ->first();

        if (!$dosen) {
            return response()->json([
                'success' => false,
                'message' => 'Dosen tidak ditemukan.',
                'data' => null
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail Data Dosen',
            'data' => [
                'nama_dosen' => $dosen->name,
                'kode_dosen' => $dosen->lecturer_code,
                'jabatan' => $dosen->jabatanStruktural?->nama, // gunakan nama dari relasi
                'home_base' => null,
                'nip' => $dosen->nip,
                'nidn' => $dosen->nidn,
                'bidang_keahlian' => $dosen->kelompokKeahlian?->nama,
                'status' => $dosen->status_pegawai,
                'contact_person' => $dosen->email,
                'jfa' => $dosen->jabatan_fungsional_akademik,
                'riwayat_pengajaran' => url("/riwayat-mengajar/{$dosen->id}"),
                'pendidikan' => $dosen->pendidikan_terakhir,
            ]
        ]);
    }

    public function assignJabatanStruktural(Request $request)
    {
        // Validasi input
        $validatedData = $request->validate([
            'id_dosen' => 'required|exists:dosens,id',
            'id_jabatan_struktural' => 'required|exists:jabatan_strukturals,id',
        ]);

        try {
            $dosen = Dosen::find($validatedData['id_dosen']);

            if (!$dosen) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dosen tidak ditemukan.',
                ], 404);
            }

            $dosen->id_jabatan_struktural = $validatedData['id_jabatan_struktural'];
            $dosen->save();

            $message = 'Jabatan Struktural berhasil di-assign ke Dosen.';

            return response()->json([
                'success' => true,
                'message' => $message,
                // 'data' => $dosen->load('jabatanStruktural'), // Muat relasi untuk respons
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            // Log::error('Error assigning Jabatan Struktural to Dosen: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan pada server.',
                'error Message' => $e
            ], 500);
        }
    }
    public function revokeJabatanStrukturalDosen(Request $request)
    {
        $validatedData = $request->validate([
            'id_dosen' => 'required|exists:dosens,id',
        ]);

        try {
            $dosen = Dosen::find($validatedData['id_dosen']);

            if (!$dosen) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dosen tidak ditemukan.',
                ], 404);
            }

            $dosen->id_jabatan_struktural = null;
            $dosen->save();

            return response()->json([
                'success' => true,
                'message' => 'Jabatan Struktural Dosen berhasil dilepas/direvoke.',
                'data' => $dosen,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            // Log::error('Error revoking Jabatan Struktural Dosen: ' . $e->getMessage() . ' | File: ' . $e->getFile() . ' | Line: ' . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan pada server saat melepas jabatan.',
            ], 500);
        }
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
