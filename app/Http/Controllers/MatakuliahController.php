<?php

namespace App\Http\Controllers;

use App\Models\Matakuliah;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MatakuliahController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    // public function index()
    // {
    //     $data = Matakuliah::with('pic')->get();
    //     return response()->json([
    //         'success' => true,
    //         'message' => 'List All Matakuliah',
    //         'data' => $data
    //     ]);
    // }

    public function index(Request $request)
    {
        $searchNamaMatakuliah = $request->query('nama_matakuliah', '');
        $searchPic = $request->query('pic', '');
        $perPage = $request->query('per_page', 9); // Default 15 item per halaman

        $query = Matakuliah::query()->with('pic'); // Eager load relasi 'pic' untuk efisiensi

        $query->when($searchNamaMatakuliah, function ($q) use ($searchNamaMatakuliah) {
            return $q->where('nama_matakuliah', 'like', "%{$searchNamaMatakuliah}%");
        });

        $query->when($searchPic, function ($q) use ($searchPic) {
            return $q->whereHas('pic', function ($picQuery) use ($searchPic) {
                $picQuery->where('name', 'like', "%{$searchPic}%");
            });
        });

        $data = $query->orderBy('nama_matakuliah', 'asc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar Mata Kuliah berhasil dimuat.',
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
    // public function store(Request $request)
    // {
    //     $validated = $request->validate([
    //         'nama_matakuliah' => 'required|string|max:255',
    //         'kode_matkul' => 'required|string|max:10|unique:matakuliahs,kode_matkul',
    //         'sks' => 'required|integer|min:1|max:6',
    //         'praktikum' => 'required|boolean',
    //         'id_pic' => 'required|exists:pics,id',
    //         'mandatory_status' => 'required|in:wajib_prodi,pilihan',
    //         'mode_perkuliahan' => 'required|in:online,onsite,hybrid',
    //     ]);

    //     $matakuliah = Matakuliah::create($validated);

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Matakuliah berhasil ditambahkan.',
    //         'data' => $matakuliah->load('pic')
    //     ], 201);
    // }

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'nama_matakuliah' => 'required|string|max:255',
                'kode_matkul' => 'required|string|max:10|unique:matakuliahs,kode_matkul', // Pastikan tabel dan kolom unik benar
                'sks' => 'required|integer|min:1|max:6', // Batas SKS bisa disesuaikan
                'praktikum' => 'required|boolean',
                'id_pic' => 'required|exists:pics,id', // Pastikan tabel 'pics' ada
                'mandatory_status' => 'required|in:wajib_prodi,pilihan',
                'mode_perkuliahan' => 'required|in:online,onsite,hybrid',
                'matakuliah_eksepsi' => 'required|in:ya,tidak',
                'tingkat_matakuliah' => 'required|in:Tingkat 1,Tingkat 2,Tingkat 3,Tingkat 4',
            ]);

            $hour_target = $validatedData['sks'] * 16;

            $dataToCreate = array_merge($validatedData, ['hour_target' => $hour_target]);

            $matakuliah = Matakuliah::create($dataToCreate);

            return response()->json([
                'success' => true,
                'message' => 'Matakuliah berhasil ditambahkan.',
                'data' => $matakuliah->load('pic') //
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan mata kuliah karena terjadi kesalahan pada server.',
            ], 500);
        }
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
