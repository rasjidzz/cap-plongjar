<?php

namespace App\Http\Controllers;

use App\Models\KelompokKeahlian;
use App\Models\Matakuliah;
use App\Models\Pic;
use App\Models\ProgramStudi;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

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
    // OLD VERSION getMatakuliahByPicProgramStudi
    public function getMatakuliahByPicProgramStudiOld(Request $request)
    {
        try {
            // 1. Dapatkan user yang sedang login
            $user = $request->user();
            $user->loadMissing('roles');
            $assignedEntityName = null;

            // 2. Cari assignment Program Studi atau Kelompok Keahlian user tersebut
            foreach ($user->roles as $role) {
                if ($role->name === 'ProgramStudi' && isset($role->pivot->roleable_id)) {
                    $programStudi = ProgramStudi::find($role->pivot->roleable_id);
                    if ($programStudi) {
                        $assignedEntityName = $programStudi->nama;
                        break;
                    }
                }
            }

            // 3. Handle jika user tidak memiliki assignment yang sesuai
            if (!$assignedEntityName) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak ter-assign ke Program Studi atau Kelompok Keahlian manapun.',
                ], 403);
            }

            // 4. Cari PIC yang namanya sama dengan nama Program Studi/KK user
            $pic = Pic::where('name', $assignedEntityName)->first();

            if (!$pic) {
                return response()->json([
                    'success' => true, // Sukses, tapi tidak ada data
                    'message' => 'Tidak ada mata kuliah yang ditemukan untuk PIC "' . $assignedEntityName . '".',
                    'data' => []
                ], 200);
            }

            // 5. Bangun query untuk mengambil mata kuliah berdasarkan id_pic
            $query = Matakuliah::query()->where('id_pic', $pic->id)->with('pic');

            // Tambahkan fungsionalitas pencarian dan paginasi
            $searchNamaMatakuliah = $request->query('nama_matakuliah', '');
            $searchKodeMatkul = $request->query('kode_matkul', '');
            $perPage = $request->query('per_page', 15);

            $query->when($searchNamaMatakuliah, fn($q) => $q->where('nama_matakuliah', 'like', "%{$searchNamaMatakuliah}%"));
            $query->when($searchKodeMatkul, fn($q) => $q->where('kode_matkul', 'like', "%{$searchKodeMatkul}%"));

            $matakuliahs = $query->orderBy('nama_matakuliah', 'asc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Daftar Mata Kuliah untuk PIC "' . $assignedEntityName . '" berhasil dimuat.',
                'data' => $matakuliahs
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan pada server.'
            ], 500);
        }
    }
    public function getMatakuliahByPicProgramStudi(Request $request)
    {
        try {
            // --- Langkah 1: Dapatkan Konteks User (Prodi/KK) ---
            $user = $request->user();
            $user->loadMissing('roles');

            $assignedEntityName = null;
            $authRoleableId = null;
            $authRoleableType = null;

            foreach ($user->roles as $role) {
                if (($role->name === 'ProgramStudi' || $role->name === 'KelompokKeahlian') && isset($role->pivot->roleable_id)) {
                    $authRoleableId = $role->pivot->roleable_id;
                    $authRoleableType = $role->pivot->roleable_type;

                    if ($authRoleableType === ProgramStudi::class || $authRoleableType === 'App\\Models\\ProgramStudi') {
                        $assignedEntityName = ProgramStudi::find($authRoleableId)?->nama;
                    } elseif ($authRoleableType === KelompokKeahlian::class || $authRoleableType === 'App\\Models\\KelompokKeahlian') {
                        $assignedEntityName = KelompokKeahlian::find($authRoleableId)?->nama;
                    }

                    if ($assignedEntityName) break;
                }
            }

            if (!$assignedEntityName || !$authRoleableId || !$authRoleableType) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak ter-assign ke Program Studi atau Kelompok Keahlian yang valid.',
                ], 403);
            }

            // --- Langkah 2: Dapatkan Kriteria Filter ---
            $pic = Pic::where('name', $assignedEntityName)->first();
            $picId = $pic ? $pic->id : null;

            $creatorUserIds = DB::table('user_roles')
                ->where('roleable_id', $authRoleableId)
                ->where('roleable_type', $authRoleableType)
                ->pluck('user_id')
                ->toArray();

            // --- Langkah 3: Bangun Query Utama ---
            $query = Matakuliah::query()->with(['pic', 'createdBy:id,name']);

            $query->where(function ($q) use ($picId, $creatorUserIds) {
                // Kondisi 1: Matakuliah yang PIC-nya adalah Prodi/KK user
                if ($picId) {
                    $q->where('id_pic', $picId);
                }

                // Kondisi 2: ATAU matakuliah yang dibuat oleh anggota Prodi/KK yang sama
                if (!empty($creatorUserIds)) {
                    $q->orWhereIn('created_by', $creatorUserIds);
                }
            });

            // Tambahkan fungsionalitas pencarian dan paginasi
            $searchTerm = $request->query('search', '');
            $perPage = $request->query('per_page', 15);

            $query->when($searchTerm, function ($q) use ($searchTerm) {
                $q->where(function ($subQuery) use ($searchTerm) {
                    $subQuery->where('nama_matakuliah', 'like', "%{$searchTerm}%")
                        ->orWhere('kode_matkul', 'like', "%{$searchTerm}%");
                });
            });

            $matakuliahs = $query->orderBy('nama_matakuliah', 'asc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Daftar Mata Kuliah PIC untuk "' . $assignedEntityName . '" berhasil dimuat. (Beserta Matakuliah Created By Sendiri)',
                'data' => $matakuliahs
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan pada server.'
            ], 500);
        }
    }
    public function getMatakuliahByPicKelompokKeahlian(Request $request)
    {
        try {
            // 1. Dapatkan user yang sedang login
            $user = $request->user();
            $user->loadMissing('roles');
            $assignedEntityName = null;

            // 2. Cari assignment Program Studi atau Kelompok Keahlian user tersebut
            foreach ($user->roles as $role) {
                if ($role->name === 'KelompokKeahlian' && isset($role->pivot->roleable_id)) {
                    $kelompokKeahlian = KelompokKeahlian::find($role->pivot->roleable_id);
                    if ($kelompokKeahlian) {
                        $assignedEntityName = $kelompokKeahlian->nama;
                        break;
                    }
                }
            }

            // 3. Handle jika user tidak memiliki assignment yang sesuai
            if (!$assignedEntityName) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak ter-assign ke Program Studi atau Kelompok Keahlian manapun.',
                ], 403);
            }

            // 4. Cari PIC yang namanya sama dengan nama Program Studi/KK user
            $pic = Pic::where('name', $assignedEntityName)->first();

            if (!$pic) {
                return response()->json([
                    'success' => true, // Sukses, tapi tidak ada data
                    'message' => 'Tidak ada mata kuliah yang ditemukan untuk PIC "' . $assignedEntityName . '".',
                    'data' => []
                ], 200);
            }

            // return response()->json([
            //     'success' => true,
            //     'message' => 'Success, Test Debug',
            //     'dataUser' => $user,
            //     'kelompokKeahlianData' => $kelompokKeahlian,
            //     'picData' => $pic
            // ], 200);

            // 5. Bangun query untuk mengambil mata kuliah berdasarkan id_pic
            $query = Matakuliah::query()->where('id_pic', $pic->id)->with('pic');

            // Tambahkan fungsionalitas pencarian dan paginasi
            $searchNamaMatakuliah = $request->query('nama_matakuliah', '');
            $searchKodeMatkul = $request->query('kode_matkul', '');
            $perPage = $request->query('per_page', 15);

            $query->when($searchNamaMatakuliah, fn($q) => $q->where('nama_matakuliah', 'like', "%{$searchNamaMatakuliah}%"));
            $query->when($searchKodeMatkul, fn($q) => $q->where('kode_matkul', 'like', "%{$searchKodeMatkul}%"));

            $matakuliahs = $query->orderBy('nama_matakuliah', 'asc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Daftar Mata Kuliah untuk PIC "' . $assignedEntityName . '" berhasil dimuat.',
                'data' => $matakuliahs
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan pada server.'
            ], 500);
        }
    }

    public function getMatakuliahForPlottingByProdiAndKK(Request $request)
    {
        try {
            // --- Langkah 1: Dapatkan Konteks User dan Daftar PIC yang Relevan ---
            $user = $request->user();
            $user->loadMissing('roles');

            $userProdiName = null;
            foreach ($user->roles as $role) {
                if ($role->name === 'ProgramStudi' && isset($role->pivot->roleable_id)) {
                    $programStudi = ProgramStudi::find($role->pivot->roleable_id);
                    if ($programStudi) {
                        $userProdiName = $programStudi->nama;
                        break;
                    }
                }
            }

            if (is_null($userProdiName)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Otorisasi gagal: Anda tidak ter-assign ke Program Studi manapun.',
                ], 403);
            }

            $kelompokKeahlianNames = KelompokKeahlian::pluck('nama')->toArray();

            $relevantPicIds = Pic::where('name', $userProdiName)
                ->orWhereIn('name', $kelompokKeahlianNames)
                ->pluck('id')
                ->toArray();

            if (empty($relevantPicIds)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tidak ada mata kuliah yang ditemukan untuk Program Studi Anda atau Kelompok Keahlian manapun.',
                    'data' => []
                ], 200);
            }

            // --- Langkah 2: Query Mata Kuliah Berdasarkan PIC yang Relevan ---
            $query = Matakuliah::query()->with('pic')->whereIn('id_pic', $relevantPicIds);

            // $searchTerm = $request->query('search', '');
            // $perPage = $request->query('per_page', 15);

            // $query->when($searchTerm, function ($q) use ($searchTerm) {
            //     $q->where(function ($subQuery) use ($searchTerm) {
            //         $subQuery->where('nama_matakuliah', 'like', "%{$searchTerm}%")
            //             ->orWhere('kode_matkul', 'like', "%{$searchTerm}%");
            //     });
            // });

            // $matakuliahs = $query->orderBy('nama_matakuliah', 'asc')->paginate($perPage);
            $matakuliahs = $query->orderBy('nama_matakuliah', 'asc')->get();

            return response()->json([
                'success' => true,
                'message' => 'Daftar Mata Kuliah untuk "' . $userProdiName . '" dan semua Kelompok Keahlian berhasil dimuat.',
                'data' => $matakuliahs
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan pada server.'
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

            // $dataToCreate = array_merge($validatedData, ['hour_target' => $hour_target]);

            $dataToCreate = array_merge($validatedData, [
                'hour_target' => $hour_target,
                'created_by' => auth()->id()
            ]);

            $matakuliah = Matakuliah::create($dataToCreate);

            return response()->json([
                'success' => true,
                'message' => 'Matakuliah berhasil ditambahkan.',
                // 'data' => $matakuliah->load('pic')
                'data' => $matakuliah->load(['pic', 'createdBy:id,name'])
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
