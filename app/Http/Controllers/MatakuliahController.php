<?php

namespace App\Http\Controllers;

use App\Models\Matakuliah;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

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
                // 'kode_matkul' => 'required|string|max:10|unique:matakuliahs,kode_matkul', // Pastikan tabel dan kolom unik benar
                'kode_matkul' => [
                    'required',
                    'string',
                    'max:10',
                    Rule::unique('matakuliahs', 'kode_matkul')->whereNull('deleted_at'),
                ],
                'sks' => 'required|integer|min:1|max:6',
                'praktikum' => 'required|boolean',
                'id_pic' => 'required|exists:pics,id',
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
        // Route model binding akan secara otomatis menangani jika mata kuliah tidak ditemukan (return 404).
        // Kita hanya perlu memuat relasi yang dibutuhkan dan mengembalikan data.
        return response()->json([
            'success' => true,
            'message' => 'Detail mata kuliah berhasil dimuat.',
            'data' => $matakuliah->load('pic') // Eager load relasi 'pic' untuk menyertakan data PIC
        ]);
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
        try {
            // Validasi data input
            $validatedData = $request->validate([
                'nama_matakuliah' => 'required|string|max:255',
                // Pastikan kode_matkul unik, kecuali untuk record yang sedang diedit
                'kode_matkul' => [
                    'required',
                    'string',
                    'max:10',
                    Rule::unique('matakuliahs', 'kode_matkul')->ignore($matakuliah->id)->whereNull('deleted_at'),
                ],
                'sks' => 'required|integer|min:1|max:6',
                'praktikum' => 'required|boolean',
                'id_pic' => 'required|exists:pics,id',
                'mandatory_status' => 'required|in:wajib_prodi,pilihan',
                'mode_perkuliahan' => 'required|in:online,onsite,hybrid',
                'matakuliah_eksepsi' => 'required|in:ya,tidak',
                'tingkat_matakuliah' => 'required|in:Tingkat 1,Tingkat 2,Tingkat 3,Tingkat 4',
            ]);

            // Hitung hour_target berdasarkan SKS baru
            $hour_target = $validatedData['sks'] * 16;
            $dataToUpdate = array_merge($validatedData, ['hour_target' => $hour_target]);

            // Lakukan update pada model
            $matakuliah->update($dataToUpdate);

            return response()->json([
                'success' => true,
                'message' => 'Mata kuliah berhasil diperbarui.',
                'data' => $matakuliah->load('pic') // Kembalikan data yang sudah diupdate dengan relasi pic
            ], 200); // HTTP 200 OK

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui mata kuliah karena terjadi kesalahan pada server.'
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Matakuliah $matakuliah, Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = $request->user();

        if (!Hash::check($request->input('password'), $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Incorrect Password, action denied',
                'errors' => [
                    'password' => ['Incorrect Password']
                ]
            ], 422); // 422 Unprocessable Entity adalah status yang baik untuk ini
        }
        try {
            // Melakukan soft delete. Eloquent akan otomatis mengisi kolom 'deleted_at'.
            $matakuliah->delete();

            return response()->json([
                'success' => true,
                'message' => 'Mata kuliah berhasil dihapus (soft delete).'
            ], 200); // 200 OK atau 204 No Content

        } catch (\Exception $e) {
            // Menangani error tak terduga
            // Log::error('Error saat menghapus mata kuliah: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus mata kuliah karena terjadi kesalahan pada server.'
            ], 500);
        }
    }
}
