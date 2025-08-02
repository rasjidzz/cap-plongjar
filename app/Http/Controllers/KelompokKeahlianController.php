<?php

namespace App\Http\Controllers;

use App\Models\KelompokKeahlian;
use Illuminate\Http\Request;

class KelompokKeahlianController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function getAllKelompokKeahlianWithKetua(Request $request)
    {
        // Ambil parameter dari query string URL
        $searchTerm = $request->query('search', '');
        $perPage = $request->query('per_page', 15); // Default 15 item per halaman

        // Mulai query dengan eager loading relasi 'ketua'
        $query = KelompokKeahlian::with('ketua:id,name,lecturer_code');

        // Terapkan filter pencarian jika ada
        $query->when($searchTerm, function ($q) use ($searchTerm) {
            // Mengelompokkan kondisi OR
            $q->where('nama', 'like', "%{$searchTerm}%") // Cari berdasarkan nama Kelompok Keahlian
                ->orWhereHas('ketua', function ($ketuaQuery) use ($searchTerm) {
                    // Cari berdasarkan nama dosen yang menjadi ketua
                    $ketuaQuery->where('name', 'like', "%{$searchTerm}%");
                });
        });

        // Lakukan paginasi dan urutkan hasilnya
        $data = $query->orderBy('nama', 'asc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar Kelompok Keahlian beserta ketua berhasil dimuat.',
            'data' => $data
        ]);
    }
    public function index()
    {
        // $kelompokKeahlian = KelompokKeahlian::all();
        $kelompokKeahlian = KelompokKeahlian::with('ketua:id,name,lecturer_code');
        $data = $kelompokKeahlian->orderBy('nama', 'asc')->get();
        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }
    public function assignKetua(Request $request, $id_kk)
    {
        // 1. Validasi request (pastikan id_dosen ada dan valid)
        $validated = $request->validate(['id_dosen' => 'required|exists:dosens,id']);

        // 2. Cari Kelompok Keahlian
        $kelompokKeahlian = KelompokKeahlian::findOrFail($id_kk);

        // 3. Update kolom id_ketua_dosen
        $kelompokKeahlian->id_ketua_dosen = $validated['id_dosen'];
        $kelompokKeahlian->save();

        // 4. Kembalikan respons sukses
        return response()->json([
            'success' => true,
            'message' => 'Ketua Kelompok Keahlian berhasil di-assign.',
            'data' => $kelompokKeahlian->load('ketua')
        ]);
    }
    public function revokeKetua($id_kk)
    {
        try {
            // 1. Cari Kelompok Keahlian, atau gagal dengan 404 jika tidak ditemukan.
            $kelompokKeahlian = KelompokKeahlian::findOrFail($id_kk);

            // 2. Cek apakah memang ada ketua yang di-assign untuk direvoke.
            if ($kelompokKeahlian->id_ketua_dosen === null) {
                return response()->json([
                    'success' => true, // Dianggap sukses karena tujuannya sudah tercapai
                    'message' => 'Kelompok Keahlian ini sudah tidak memiliki ketua.',
                    'data' => $kelompokKeahlian
                ], 200);
            }

            // 3. Atur foreign key ketua menjadi null.
            $kelompokKeahlian->id_ketua_dosen = null;
            $kelompokKeahlian->save();

            // 4. Kembalikan respons sukses.
            return response()->json([
                'success' => true,
                'message' => 'Ketua Kelompok Keahlian berhasil dicabut (revoke).',
                'data' => $kelompokKeahlian
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan pada server saat mencabut ketua.',
            ], 500);
        }
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
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(KelompokKeahlian $kelompokKeahlian)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(KelompokKeahlian $kelompokKeahlian)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, KelompokKeahlian $kelompokKeahlian)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(KelompokKeahlian $kelompokKeahlian)
    {
        //
    }
}
