<?php

namespace App\Http\Controllers;

use App\Models\MappingKelasMatakuliah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    public function getMappingKelasMatkulByIdMatkulandIdTahunAjaran($id_matakuliah, $id_tahunajaran) // ** ini base
    {
        $data = MappingKelasMatakuliah::with([
            'matakuliah' => function ($matakuliahQuery) {
                $matakuliahQuery->with('pic:id,name');
            },
            'tahunAjaran:id,tahun_ajaran,semester',
            'plottinganPengajarans:id,id_mapping_kelas_matakuliah,id_dosen',
            'plottinganPengajarans.dosen:id,name,lecturer_code'
        ])
            ->where('id_matakuliah', $id_matakuliah)
            ->where('id_tahun_ajaran', $id_tahunajaran)
            ->orderBy('nama_kelas', 'asc')
            ->get();

        if ($data->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'Tidak ada data mapping kelas mata kuliah yang ditemukan untuk kriteria yang diberikan.',
                'data' => []
            ], 200);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data mapping kelas mata kuliah berhasil dimuat.',
            'data' => $data
        ]);
    }

    public function getMappingKelasMatakuliahByIdMatakuliahIdTahunAjaranandIdProgramStudi($id_matakuliah, $id_tahun_ajaran, $id_program_studi) // ** ini ganti
    {
        // $data = MappingKelasMatakuliah::with(['matakuliah', 'tahunAjaran', 'programStudi'])
        //     ->where('id_matakuliah', $id_matakuliah)
        //     ->where('id_tahun_ajaran', $id_tahun_ajaran)
        //     ->where('id_program_studi', $id_program_studi)
        //     ->get();
        $data = MappingKelasMatakuliah::with([
            'matakuliah' => function ($matakuliahQuery) {
                $matakuliahQuery->with('pic:id,name');
            },
            'tahunAjaran:id,tahun_ajaran,semester',
            'plottinganPengajarans:id,id_mapping_kelas_matakuliah,id_dosen',
            'plottinganPengajarans.dosen:id,name,lecturer_code'
        ])
            ->where('id_matakuliah', $id_matakuliah)
            ->where('id_tahun_ajaran', $id_tahun_ajaran)
            ->orderBy('nama_kelas', 'asc')
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

    // public function store(Request $request)
    // {
    //     $validatedData = $request->validate([
    //         'id_matakuliah' => 'required|integer|exists:matakuliahs,id',
    //         'id_tahun_ajaran' => 'required|integer|exists:tahun_ajarans,id',
    //         // 'id_program_studi' => 'required|integer|exists:program_studis,id',
    //         'classes' => 'required|array|min:1',
    //         'classes.*.nama_kelas' => [
    //             'required',
    //             'string',
    //             'max:255',
    //             function ($attribute, $value, $fail) use ($request) {
    //                 $dbExists = MappingKelasMatakuliah::where('id_matakuliah', $request->input('id_matakuliah'))
    //                     ->where('id_tahun_ajaran', $request->input('id_tahun_ajaran'))
    //                     ->where('nama_kelas', $value)
    //                     // ->whereNull('deleted_at') // Soft Delete
    //                     ->exists();

    //                 if ($dbExists) {
    //                     $fail("Nama kelas '{$value}' pada {$attribute} sudah terdaftar untuk mata kuliah dan tahun ajaran ini di database.");
    //                     return;
    //                 }

    //                 $allNamaKelasInRequest = array_column($request->input('classes', []), 'nama_kelas');
    //                 $namaKelasCounts = array_count_values(array_filter($allNamaKelasInRequest));

    //                 if (isset($namaKelasCounts[$value]) && $namaKelasCounts[$value] > 1) {
    //                     $fail("Nama kelas '{$value}' pada {$attribute} muncul lebih dari sekali dalam daftar kelas yang dikirim.");
    //                 }
    //             },
    //         ],
    //         'classes.*.kuota' => 'required|integer|min:1',
    //         'classes.*.team_teaching' => 'required|boolean',
    //     ], [
    //         'id_matakuliah.required' => 'ID Mata Kuliah wajib diisi.',
    //         'id_matakuliah.exists' => 'ID Mata Kuliah yang dipilih tidak valid.',
    //         'id_tahun_ajaran.required' => 'ID Tahun Ajaran wajib diisi.',
    //         'id_tahun_ajaran.exists' => 'ID Tahun Ajaran yang dipilih tidak valid.',
    //         'classes.required' => 'Daftar kelas wajib diisi.',
    //         'classes.array' => 'Daftar kelas harus berupa array.',
    //         'classes.min' => 'Minimal harus ada satu kelas dalam daftar.',
    //         'classes.*.nama_kelas.required' => 'Nama kelas untuk :attribute wajib diisi.',
    //         'classes.*.nama_kelas.string' => 'Nama kelas untuk :attribute harus berupa teks.',
    //         'classes.*.nama_kelas.max' => 'Nama kelas untuk :attribute maksimal 255 karakter.',
    //         'classes.*.kuota.required' => 'Kuota untuk :attribute wajib diisi.',
    //         'classes.*.kuota.integer' => 'Kuota untuk :attribute harus berupa angka.',
    //         'classes.*.kuota.min' => 'Kuota untuk :attribute minimal 1.',
    //         'classes.*.team_teaching.required' => 'Status team teaching untuk :attribute wajib diisi.',
    //         'classes.*.team_teaching.boolean' => 'Status team teaching untuk :attribute harus boolean (true/false atau 1/0).',
    //     ]);

    //     $createdMappings = [];
    //     try {
    //         foreach ($validatedData['classes'] as $classData) {
    //             $mapping = MappingKelasMatakuliah::create([
    //                 'id_matakuliah'   => $validatedData['id_matakuliah'],
    //                 'id_tahun_ajaran' => $validatedData['id_tahun_ajaran'],
    //                 'id_program_studi' => $validatedData['id_program_studi'],
    //                 'nama_kelas'      => $classData['nama_kelas'],
    //                 'kuota'           => $classData['kuota'],
    //                 'team_teaching'   => $classData['team_teaching'],
    //             ]);
    //             $createdMappings[] = $mapping;
    //         }

    //         return response()->json([
    //             'success' => true,
    //             'message' => count($createdMappings) . ' mapping kelas mata kuliah berhasil ditambahkan.',
    //             'data' => $createdMappings
    //         ], 201);
    //     } catch (\Illuminate\Validation\ValidationException $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => $e->getMessage(),
    //             'errors' => $e->errors(),
    //         ], 422);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Terjadi kesalahan pada server saat menambahkan mapping kelas.',
    //         ], 500);
    //     }
    // }
    public function store(Request $request)
    {
        $user = $request->user();
        $user->loadMissing('roles');

        $userProdiId = null;
        foreach ($user->roles as $role) {
            if ($role->name === 'ProgramStudi' && isset($role->pivot->roleable_id)) {
                $userProdiId = $role->pivot->roleable_id;
                break;
            }
        }

        if (is_null($userProdiId)) {
            $isSuperAdmin = $user->roles->contains('id', 1);
            if (!$isSuperAdmin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Otorisasi gagal: Anda tidak ter-assign ke Program Studi manapun.',
                ], 403);
            }
        }

        $validatedData = $request->validate([
            'id_matakuliah' => 'required|integer|exists:matakuliahs,id',
            'id_tahun_ajaran' => 'required|integer|exists:tahun_ajarans,id',
            'id_program_studi' => ($userProdiId ? 'sometimes|prohibited' : 'required|integer|exists:program_studis,id'),
            'classes' => 'required|array|min:1',
            'classes.*.nama_kelas' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($request, $userProdiId) {
                    $prodiIdToCheck = $userProdiId ?? $request->input('id_program_studi');

                    if (!$prodiIdToCheck) {
                        $fail('Program Studi tidak dapat ditentukan.');
                        return;
                    }

                    $dbExists = MappingKelasMatakuliah::where('id_matakuliah', $request->input('id_matakuliah'))
                        ->where('id_tahun_ajaran', $request->input('id_tahun_ajaran'))
                        ->where('id_program_studi', $prodiIdToCheck)
                        ->where('nama_kelas', $value)
                        ->exists();

                    if ($dbExists) {
                        $fail("Nama kelas '{$value}' sudah terdaftar untuk mata kuliah, tahun ajaran, dan program studi ini.");
                        return;
                    }

                    $allNamaKelasInRequest = array_column($request->input('classes', []), 'nama_kelas');
                    $namaKelasCounts = array_count_values(array_filter($allNamaKelasInRequest));

                    if (isset($namaKelasCounts[$value]) && $namaKelasCounts[$value] > 1) {
                        $fail("Nama kelas '{$value}' muncul lebih dari sekali dalam daftar.");
                    }
                },
            ],
            'classes.*.kuota' => 'required|integer|min:1',
            'classes.*.team_teaching' => 'required|boolean',
        ], [
            'id_program_studi.required' => 'Sebagai Superadmin, Anda wajib memilih Program Studi.',
            'id_program_studi.prohibited' => 'Anda tidak perlu mengirim ID Program Studi karena sudah ter-assign ke prodi tertentu.',
        ]);

        $id_prodi_to_use = $userProdiId ?? $validatedData['id_program_studi'];

        $createdMappings = [];
        // return response()->json([
        //     'success' => true,
        //     'data' => $createdMappings,
        //     'dataUser' => $user
        // ], 201);
        DB::beginTransaction();
        try {
            foreach ($validatedData['classes'] as $classData) {
                $mapping = MappingKelasMatakuliah::create([
                    'id_matakuliah'   => $validatedData['id_matakuliah'],
                    'id_tahun_ajaran' => $validatedData['id_tahun_ajaran'],
                    'id_program_studi' => $id_prodi_to_use,
                    'nama_kelas'      => $classData['nama_kelas'],
                    'kuota'           => $classData['kuota'],
                    'team_teaching'   => $classData['team_teaching'],
                ]);
                $createdMappings[] = $mapping;
            }
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => count($createdMappings) . ' mapping kelas mata kuliah berhasil ditambahkan.',
                'data' => $createdMappings
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan pada server saat menambahkan mapping kelas.',
            ], 500);
        }
    }

    public function getMappingByMatkulTahunAjaranAndAuthProdi(Request $request, $id_matakuliah, $id_tahun_ajaran)
    {
        $user = $request->user();
        $userProdiId = null;

        $user->loadMissing('roles');

        foreach ($user->roles as $role) {
            if ($role->name === 'ProgramStudi' && isset($role->pivot->roleable_id)) {
                $userProdiId = $role->pivot->roleable_id;
                break;
            }
        }

        if (is_null($userProdiId)) {
            return response()->json([
                'success' => false,
                'message' => 'Otorisasi gagal: Anda tidak ter-assign ke Program Studi manapun.',
            ], 403); // 403 Forbidden
        }

        $query = MappingKelasMatakuliah::query()->with([
            'matakuliah.pic',
            'tahunAjaran',
            'programStudi',
            'plottinganPengajarans.dosen',
            'koordinatorMatakuliah.dosen',
        ]);

        $query->where('id_matakuliah', $id_matakuliah)
            ->where('id_tahun_ajaran', $id_tahun_ajaran)
            ->where('id_program_studi', $userProdiId);

        $data = $query->orderBy('nama_kelas', 'asc')->get();

        $formattedData = $data->map(function ($mapping) {
            return [
                'nama_kelas' => $mapping->nama_kelas,
                'kuota' => $mapping->kuota,
                'team_teaching' => (bool)$mapping->team_teaching,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Data mapping kelas mata kuliah berhasil dimuat.',
            'data' => $formattedData
        ]);
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
