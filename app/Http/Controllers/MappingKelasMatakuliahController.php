<?php

namespace App\Http\Controllers;

use App\Models\MappingKelasMatakuliah;
use Illuminate\Http\Request;

class MappingKelasMatakuliahController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = MappingKelasMatakuliah::with(['matakuliah', 'tahunAjaran'])->get();
        return response()->json([
            'success' => true,
            'message' => 'List Mapping Kelas Matakuliah',
            'data' => $data
        ]);
    }

    public function getMappingKelasMatkulbyIdMatkul($id_matakuliah)
    {
        $data = MappingKelasMatakuliah::with(['matakuliah', 'tahunAjaran'])
            ->where('id_matakuliah', $id_matakuliah)
            ->get();

        if ($data->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Mapping kelas matakuliah tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    public function getMappingKelasMatkulByIdMatkulandIdTahunAjaran($id_matakuliah, $id_tahunajaran)
    {
        $data = MappingKelasMatakuliah::with([
            'matakuliah:id,kode_matkul,nama_matakuliah,sks', // Memuat detail mata kuliah
            'tahunAjaran:id,tahun_ajaran,semester',         // Memuat detail tahun ajaran
            'plottinganPengajarans:id,id_mapping_kelas_matakuliah,id_dosen', // Memuat plottingan
            'plottinganPengajarans.dosen:id,name,lecturer_code' // Memuat dosen dari plottingan
        ])
            ->where('id_matakuliah', $id_matakuliah)
            ->where('id_tahun_ajaran', $id_tahunajaran)
            ->orderBy('nama_kelas', 'asc') // Urutkan berdasarkan nama kelas
            ->get();

        if ($data->isEmpty()) {
            return response()->json([
                'success' => true, // Atau false jika Anda menganggap tidak ada data sebagai "error"
                'message' => 'Tidak ada data mapping kelas mata kuliah yang ditemukan untuk kriteria yang diberikan.',
                'data' => []
            ], 200); // atau 404 jika Anda ingin mengindikasikan resource tidak ditemukan
        }

        return response()->json([
            'success' => true,
            'message' => 'Data mapping kelas mata kuliah berhasil dimuat.',
            'data' => $data
        ]);
    }

    public function getMappingKelasMatakuliahByIdMatakuliahIdTahunAjaranandIdProgramStudi($id_matakuliah, $id_tahun_ajaran, $id_program_studi)
    {
        $data = MappingKelasMatakuliah::with(['matakuliah', 'tahunAjaran', 'programStudi'])
            ->where('id_matakuliah', $id_matakuliah)
            ->where('id_tahun_ajaran', $id_tahun_ajaran)
            ->where('id_program_studi', $id_program_studi)
            ->get();
        if ($data->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'Tidak ada data mapping kelas mata kuliah yang ditemukan dengan Id Matkul: ' . $id_matakuliah . ', id tahun ajaran ' . $id_tahun_ajaran . ', dan id program studi ' . $id_program_studi,
                'data' => []
            ], 200);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data dengan Id Matkul: ' . $id_matakuliah . ', id tahun ajaran ' . $id_tahun_ajaran . ', dan id program studi ' . $id_program_studi .  'mapping kelas mata kuliah berhasil dimuat.',
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
    //         'id_matakuliah' => 'required|exists:matakuliahs,id',
    //         'id_tahun_ajaran' => 'required|exists:tahun_ajarans,id',
    //         'nama_kelas' => 'required|string|max:255',
    //         'kuota' => 'required|integer|min:1',
    //         'team_teaching' => 'required|boolean',
    //     ]);

    //     $mapping = MappingKelasMatakuliah::create($validated);

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Mapping kelas matakuliah berhasil ditambahkan.',
    //         'data' => $mapping
    //     ], 201);
    // }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'id_matakuliah' => 'required|integer|exists:matakuliahs,id',
            'id_tahun_ajaran' => 'required|integer|exists:tahun_ajarans,id',
            'classes' => 'required|array|min:1', // Pastikan 'classes' adalah array dan tidak kosong
            'classes.*.nama_kelas' => [ // Validasi untuk setiap 'nama_kelas' dalam array 'classes'
                'required',
                'string',
                'max:255',
                // Custom rule untuk memastikan nama_kelas unik
                function ($attribute, $value, $fail) use ($request) {
                    // $attribute akan menjadi seperti "classes.0.nama_kelas"
                    // $value adalah isi dari nama_kelas saat ini

                    // 1. Cek keunikan terhadap database
                    $dbExists = MappingKelasMatakuliah::where('id_matakuliah', $request->input('id_matakuliah'))
                        ->where('id_tahun_ajaran', $request->input('id_tahun_ajaran'))
                        ->where('nama_kelas', $value)
                        // ->whereNull('deleted_at') // Jika Anda menggunakan SoftDeletes
                        ->exists();

                    if ($dbExists) {
                        // Menggunakan $attribute untuk memberikan info index mana yang error
                        $fail("Nama kelas '{$value}' pada {$attribute} sudah terdaftar untuk mata kuliah dan tahun ajaran ini di database.");
                        return; // Hentikan pengecekan lebih lanjut untuk item ini jika sudah ada di DB
                    }

                    // 2. Cek keunikan nama_kelas di dalam array 'classes' pada request saat ini
                    $allNamaKelasInRequest = array_column($request->input('classes', []), 'nama_kelas');
                    // Filter nilai null atau kosong jika nama_kelas bisa opsional di beberapa kasus (meskipun di sini 'required')
                    $namaKelasCounts = array_count_values(array_filter($allNamaKelasInRequest));

                    if (isset($namaKelasCounts[$value]) && $namaKelasCounts[$value] > 1) {
                        $fail("Nama kelas '{$value}' pada {$attribute} muncul lebih dari sekali dalam daftar kelas yang dikirim.");
                    }
                },
            ],
            'classes.*.kuota' => 'required|integer|min:1',
            'classes.*.team_teaching' => 'required|boolean',
        ], [
            // Pesan kustom untuk validasi umum
            'id_matakuliah.required' => 'ID Mata Kuliah wajib diisi.',
            'id_matakuliah.exists' => 'ID Mata Kuliah yang dipilih tidak valid.',
            'id_tahun_ajaran.required' => 'ID Tahun Ajaran wajib diisi.',
            'id_tahun_ajaran.exists' => 'ID Tahun Ajaran yang dipilih tidak valid.',
            'classes.required' => 'Daftar kelas wajib diisi.',
            'classes.array' => 'Daftar kelas harus berupa array.',
            'classes.min' => 'Minimal harus ada satu kelas dalam daftar.',
            'classes.*.nama_kelas.required' => 'Nama kelas untuk :attribute wajib diisi.',
            'classes.*.nama_kelas.string' => 'Nama kelas untuk :attribute harus berupa teks.',
            'classes.*.nama_kelas.max' => 'Nama kelas untuk :attribute maksimal 255 karakter.',
            'classes.*.kuota.required' => 'Kuota untuk :attribute wajib diisi.',
            'classes.*.kuota.integer' => 'Kuota untuk :attribute harus berupa angka.',
            'classes.*.kuota.min' => 'Kuota untuk :attribute minimal 1.',
            'classes.*.team_teaching.required' => 'Status team teaching untuk :attribute wajib diisi.',
            'classes.*.team_teaching.boolean' => 'Status team teaching untuk :attribute harus boolean (true/false atau 1/0).',
        ]);

        $createdMappings = [];
        try {
            foreach ($validatedData['classes'] as $classData) {
                $mapping = MappingKelasMatakuliah::create([
                    'id_matakuliah'   => $validatedData['id_matakuliah'],
                    'id_tahun_ajaran' => $validatedData['id_tahun_ajaran'],
                    'nama_kelas'      => $classData['nama_kelas'],
                    'kuota'           => $classData['kuota'],
                    'team_teaching'   => $classData['team_teaching'],
                ]);
                $createdMappings[] = $mapping;
            }

            return response()->json([
                'success' => true,
                'message' => count($createdMappings) . ' mapping kelas mata kuliah berhasil ditambahkan.',
                'data' => $createdMappings
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Seharusnya sudah ditangani oleh $request->validate() di atas,
            // tapi sebagai catch-all jika ada cara lain ValidationException terlempar
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            // Jika ada error saat pembuatan setelah validasi (misalnya masalah DB),
            // Anda mungkin perlu me-rollback data yang sudah terbuat jika transaksinya parsial.
            // Untuk kesederhanaan, di sini kita hanya mengembalikan error umum.
            // Pertimbangkan penggunaan DB::transaction() jika ini adalah operasi atomik.
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan pada server saat menambahkan mapping kelas.',
                // 'created_mappings_before_error' => $createdMappings // Opsional, untuk debug
            ], 500);
        }
    }
    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $data = MappingKelasMatakuliah::with(['matakuliah', 'tahunAjaran'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Detail Mapping Kelas Matakuliah',
            'data' => $data
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MappingKelasMatakuliah $mappingKelasMatakuliah)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $mapping = MappingKelasMatakuliah::findOrFail($id);

        $validated = $request->validate([
            'id_matakuliah' => 'sometimes|exists:matakuliahs,id',
            'id_tahun_ajaran' => 'sometimes|exists:tahun_ajarans,id',
            'nama_kelas' => 'sometimes|string|max:255',
            'kuota' => 'sometimes|integer|min:1',
            'team_teaching' => 'sometimes|boolean',
        ]);

        $mapping->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Mapping kelas matakuliah berhasil diperbarui.',
            'data' => $mapping
        ]);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MappingKelasMatakuliah $mappingKelasMatakuliah)
    {
        //
    }
}
