<?php

namespace App\Http\Controllers;

use App\Models\TahunAjaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TahunAjaranController extends Controller
{
    /**
     * Display a listing of the resource.
     */
     // @codeCoverageIgnoreStart
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
    // @codeCoverageIgnoreEnd

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
    public function setActiveTahunAjaran($id_tahun_ajaran)
    {
        // 1. Cari tahun ajaran yang akan diaktifkan
        $tahunAjaranToActivate = TahunAjaran::find($id_tahun_ajaran);

        // 2. Validasi jika tahun ajaran tidak ditemukan
        if (!$tahunAjaranToActivate) {
            return response()->json([
                'success' => false,
                'message' => 'Tahun Ajaran dengan ID ' . $id_tahun_ajaran . ' tidak ditemukan.',
            ], 404); // 404 Not Found
        }

        try {
            DB::transaction(function () use ($tahunAjaranToActivate) {
                TahunAjaran::query()->update(['status' => false]);

                $tahunAjaranToActivate->status = true;
                $tahunAjaranToActivate->save();
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan pada server saat memperbarui status tahun ajaran.',
            ], 500);
        }

        // 4. Kembalikan respons sukses
        return response()->json([
            'success' => true,
            'message' => 'Status Tahun Ajaran berhasil diperbarui. ' . $tahunAjaranToActivate->tahun_ajaran . ' (' . $tahunAjaranToActivate->semester . ') sekarang aktif.',
            'data' => $tahunAjaranToActivate
        ], 200);
    }

    // @codeCoverageIgnoreStart
    public function getActiveTahunAjaran()
    {
        // Menggunakan scope 'active' yang sudah kita buat di model
        $activeTahunAjaran = TahunAjaran::where('status', true)->first();

        if (!$activeTahunAjaran) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada tahun ajaran yang sedang aktif saat ini.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tahun ajaran aktif berhasil dimuat.',
            'data' => $activeTahunAjaran
        ]);
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
        // Validasi: Jangan biarkan user menghapus tahun ajaran yang sedang aktif.
        if ($tahunAjaran->status === true) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat menghapus tahun ajaran yang sedang aktif. Silakan aktifkan tahun ajaran lain terlebih dahulu.'
            ], 422);
        }

        try {
            $tahunAjaran->delete();

            return response()->json([
                'success' => true,
                'message' => 'Tahun Ajaran berhasil dihapus (soft delete).'
            ], 200); // 200 OK

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus Tahun Ajaran karena terjadi kesalahan pada server.'
            ], 500);
        }
    }
    // @codeCoverageIgnoreEnd
}
