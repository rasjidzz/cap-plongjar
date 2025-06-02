<?php

namespace App\Http\Controllers;

use App\Models\MappingKelasMatakuliah;
use App\Models\Matakuliah;
use App\Models\PlottinganPengajaran;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use App\Models\Dosen;
use App\Models\KelompokKeahlian;
use App\Models\ProgramStudi;

class PlottinganPengajaranController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
    //         'id_dosen' => 'required|exists:dosens,id',
    //         'id_mapping_kelas_matakuliah' => 'required|exists:mapping_kelas_matakuliahs,id',
    //         'beban_sks' => 'integer|min:1',
    //     ]);
    //     $dosen = Dosen::find($validated['id_dosen']);
    //     $mapping_matkul = MappingKelasMatakuliah::find($validated['id_mapping_kelas_matakuliah']);
    //     $matakuliah = Matakuliah::find($mapping_matkul->id_matakuliah);
    //     $sks_matakuliah = $matakuliah->sks;
    //     $total_sks1 = $dosen->getTotalSKS((int)$mapping_matkul->id_tahun_ajaran);
    //     $total_sks2 = $dosen->getTotalSksMengajarPadaTahunAjaran((int)$mapping_matkul->id_tahun_ajaran);
    //     $data = [
    //         'total_sks1' => $total_sks1,
    //         'total_sks2' => $total_sks2
    //     ];
    //     // Apakah Mapping Kelas Matkul Team Teaching
    //     if ($mapping_matkul->team_teaching === 1) {
    //         // Proses cek apakah beban sks team teaching melebihi sks matakuliah
    //         if ($validated['beban_sks'] > $sks_matakuliah) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Beban SKS dosen melebih SKS Matakuliah Kelas',
    //                 'data' => $data
    //             ], 422);
    //         }
    //         return response()->json([
    //             'success' => true,
    //             // 'message' => 'Plotting pengajaran berhasil disimpan.',
    //             'message' => 'Masuk Kesini klo Team teaching',
    //             'beban_sks' => $validated['beban_sks'],
    //             'data' => $data
    //         ], 201);
    //         // Jika Tidak Team Teaching
    //     } else {
    //         $plottingan_pengajaran = PlottinganPengajaran::create(
    //             [
    //                 'id_dosen' => $validated['id_dosen'],
    //                 'id_mapping_kelas_matakuliah' => $validated['id_mapping_kelas_matakuliah'],
    //                 'beban_sks' => $sks_matakuliah,
    //             ]
    //         );
    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Plotting pengajaran berhasil disimpan.',
    //             'plottingan_pengajaran' => $plottingan_pengajaran,
    //             'data' => $data
    //         ], 201);
    //     }

    // $plottingan_pengajaran = PlottinganPengajaran::create(
    //     [
    //         'id_dosen' => $validated['id_dosen'],
    //         'id_mapping_kelas_matakuliah' => $validated['id_mapping_kelas_matakuliah'],
    //         'beban_sks' => $validated['beban_sks']
    //     ]
    // );


    // debug
    // $data = [
    //     'validated' => $validated,
    //     'dosen' => $dosen,
    //     'mapping_kelas_matkul' => $mapping_matkul,
    //     'matkul' => $matakuliah,
    //     'teamteaching' => $team_teaching,
    //     'total_sks_matkul' => $sks_matakuliah
    // ];
    // debug

    // return response()->json([
    //     'success' => true,
    //     'message' => 'Plotting pengajaran berhasil disimpan.',
    //     'plottingan_pengajaran' => $plottingan_pengajaran
    //     // 'data' => $data
    // ], 201);
    // }

    public function store(Request $request)
    {
        // Validasi input awal
        $validatedData = $request->validate([
            'id_dosen' => [
                'required',
                'exists:dosens,id',
                Rule::unique('plottingan_pengajarans')->where(function ($query) use ($request) {
                    return $query->where('id_mapping_kelas_matakuliah', $request->input('id_mapping_kelas_matakuliah'))
                        ->whereNull('deleted_at');
                }),
            ],
            'id_mapping_kelas_matakuliah' => 'required|exists:mapping_kelas_matakuliahs,id',
            'beban_sks' => 'nullable|integer|min:1', // Membuat beban_sks opsional di validasi awal
        ], [
            'id_dosen.unique' => 'Kombinasi Dosen dan Kelas Mata Kuliah ini sudah diplot sebelumnya.',
            'beban_sks.integer' => 'Beban SKS harus berupa angka.',
            'beban_sks.min' => 'Beban SKS minimal adalah 1.',
        ]);

        try {
            $input_beban_sks = 0;
            $mapping_matkul = MappingKelasMatakuliah::find($validatedData['id_mapping_kelas_matakuliah']);

            if (!$mapping_matkul) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mapping Kelas Mata Kuliah tidak ditemukan.',
                ], 404);
            }

            // $matakuliah = Matakuliah::find($mapping_matkul->id_matakuliah);
            $matakuliah = Matakuliah::with('pic')->find($mapping_matkul->id_matakuliah);

            if (!$matakuliah) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mata Kuliah terkait dengan mapping tidak ditemukan.',
                ], 404);
            }

            // Proses Validasi Otorisasi Role
            // Cek Apakah User yang sedang login memiliki role "KelompokKeahlian" atau "ProgramStudi"
            // dan ProgramStudi atau KelompokKeahlian apa ?
            // Jika nama ProgramStudi atau KelompokKeahlian nya tidak sama dengan $pic, maka tidak bisa lanjut
            // Bisa dilihat dari tabel "user_roles" memiliki role_id, roleable_id, dan roleable_type

            $user = $request->user();
            $user->loadMissing('roles.pivot');
            $picOfMatakuliah = $matakuliah->pic;
            $picName = $picOfMatakuliah->name;

            // return response()->json([
            //     'success' => true,
            //     'message' => 'Debug Role',
            //     'role' => $user->roles
            // ], 201);

            $isAuthorized = false;
            $userHasRelevantRole = false;

            foreach ($user->roles as $role) {
                // ID Role: 2 untuk ProgramStudi, 3 untuk KelompokKeahlian
                if ($role->id == 2) { // Role ProgramStudi
                    $userHasRelevantRole = true;
                    // Pastikan pivot dan atribut-atributnya ada sebelum diakses
                    if (
                        isset($role->pivot, $role->pivot->roleable_type, $role->pivot->roleable_id) &&
                        ($role->pivot->roleable_type === ProgramStudi::class || $role->pivot->roleable_type === 'App\\Models\\ProgramStudi')
                    ) {
                        $programStudi = ProgramStudi::find($role->pivot->roleable_id);
                        if ($programStudi && $programStudi->nama === $picName) {
                            $isAuthorized = true;
                            break;
                        }
                    }
                } elseif ($role->id == 3) { // Role KelompokKeahlian
                    $userHasRelevantRole = true;
                    // Pastikan pivot dan atribut-atributnya ada sebelum diakses
                    if (
                        isset($role->pivot, $role->pivot->roleable_type, $role->pivot->roleable_id) &&
                        ($role->pivot->roleable_type === KelompokKeahlian::class || $role->pivot->roleable_type === 'App\\Models\\KelompokKeahlian')
                    ) {
                        $kelompokKeahlian = KelompokKeahlian::find($role->pivot->roleable_id);
                        if ($kelompokKeahlian && $kelompokKeahlian->nama === $picName) {
                            $isAuthorized = true;
                            break;
                        }
                    }
                }
            }

            // return response()->json([
            //     'success' => true,
            //     'message' => 'Anda memiliki role yang sesuai untuk melakukan aksi ini.',
            //     'role' => [
            //         $isAuthorized,
            //         $userHasRelevantRole
            //     ]
            // ], 201);

            // Jika user memiliki role ProgramStudi atau KelompokKeahlian tapi tidak ada yang cocok dengan PIC
            if ($userHasRelevantRole && !$isAuthorized) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak berwenang melakukan plotting untuk mata kuliah dengan PIC (' . $picName . '). Program Studi/Kelompok Keahlian Anda tidak sesuai.',
                ], 403); // 403 Forbidden
            }

            if (!$isAuthorized && $userHasRelevantRole) { // Hanya blok jika user punya role relevan tapi tidak cocok
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak berwenang melakukan plotting untuk mata kuliah dengan PIC (' . $picName . '). Program Studi/Kelompok Keahlian Anda tidak sesuai.',
                ], 403);
            }

            $isSuperAdmin = false;
            foreach ($user->roles as $role) {
                if ($role->id == 1) { // ID 1 untuk Superadmin
                    $isSuperAdmin = true;
                    break;
                }
            }

            if (!$isSuperAdmin && !$isAuthorized && $userHasRelevantRole) { // Jika bukan superadmin, dan role relevan ada tapi tidak cocok PIC
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak berwenang melakukan plotting untuk mata kuliah dengan PIC (' . $picName . '). Program Studi/Kelompok Keahlian Anda tidak sesuai.',
                ], 403);
            } elseif (!$isSuperAdmin && !$userHasRelevantRole) {
                // Jika bukan superadmin dan tidak punya role ProgramStudi/KK sama sekali
                // Ini seharusnya sudah ditangani oleh middleware di route, tapi sebagai pengaman tambahan
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki role yang sesuai untuk melakukan aksi ini.',
                ], 403);
            }

            // return response()->json([
            //     'success' => true,
            //     'message' => 'Anda memiliki role yang sesuai untuk melakukan aksi ini.',
            //     'role' => [
            //         $isSuperAdmin,
            //         $isAuthorized,
            //         $userHasRelevantRole
            //     ]
            // ], 201);
            // Proses Validasi Otorisasi Role

            $sks_matakuliah = $matakuliah->sks;

            $dataToCreate = [
                'id_dosen' => $validatedData['id_dosen'],
                'id_mapping_kelas_matakuliah' => $validatedData['id_mapping_kelas_matakuliah'],
            ];

            if ($mapping_matkul->team_teaching === 1) {
                // TEAM TEACHING LOGIC
                // Untuk team teaching, 'beban_sks' dari input wajib ada
                if (!$request->filled('beban_sks')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Beban SKS wajib diisi untuk mode team teaching.',
                        'errors' => [
                            'beban_sks' => ['Beban SKS wajib diisi untuk team teaching.']
                        ]
                    ], 422);
                }
                $input_beban_sks = (int)$request->input('beban_sks'); // Ambil dari request langsung

                // Validasi 1: Beban SKS yang diinput untuk dosen ini tidak boleh melebihi total SKS mata kuliah
                if ($input_beban_sks > $sks_matakuliah) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Beban SKS yang diinput (' . $input_beban_sks . ') untuk dosen ini tidak boleh melebihi total SKS mata kuliah (' . $sks_matakuliah . ').',
                        'errors' => [
                            'beban_sks' => ['Beban SKS input melebihi SKS mata kuliah.']
                        ]
                    ], 422);
                }

                // Hitung total SKS yang sudah diplot ke kelas ini oleh dosen lain
                $total_sks_dosen_lain_di_kelas_ini = PlottinganPengajaran::where('id_mapping_kelas_matakuliah', $mapping_matkul->id)
                    ->sum('beban_sks');

                $potensi_total_sks_untuk_kelas_ini = $total_sks_dosen_lain_di_kelas_ini + $input_beban_sks;

                if ($potensi_total_sks_untuk_kelas_ini > $sks_matakuliah) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Total beban SKS untuk kelas ini (' . $potensi_total_sks_untuk_kelas_ini . ') akan melebihi SKS mata kuliah (' . $sks_matakuliah . '). SKS sudah terplot oleh dosen lain: ' . $total_sks_dosen_lain_di_kelas_ini . '.',
                        'errors' => [
                            'beban_sks' => ['Penambahan SKS ini akan membuat total SKS kelas melebihi batas SKS mata kuliah.']
                        ]
                    ], 422);
                }
                $dataToCreate['beban_sks'] = $input_beban_sks;
            } else {
                // TIDAK TEAM TEACHING (SOLO)
                // Beban SKS otomatis sama dengan SKS mata kuliah, input 'beban_sks' dari request diabaikan.

                $existingPlottinganSolo = PlottinganPengajaran::where('id_mapping_kelas_matakuliah', $mapping_matkul->id)->first();
                $input_beban_sks = $sks_matakuliah;
                if ($existingPlottinganSolo) {
                    $dosenExisting = Dosen::find($existingPlottinganSolo->id_dosen);
                    return response()->json([
                        'success' => false,
                        'message' => 'Kelas ini (' . $mapping_matkul->nama_kelas . ' - ' . $matakuliah->nama_matakuliah . ') sudah diplot ke dosen lain (' . ($dosenExisting ? $dosenExisting->name : 'ID:' . $existingPlottinganSolo->id_dosen) . ') karena bukan mode team teaching.',
                        'errors' => [
                            'id_mapping_kelas_matakuliah' => ['Kelas ini sudah memiliki dosen pengajar (mode solo).']
                        ]
                    ], 422); // Konflik atau Unprocessable
                }

                // Beban SKS otomatis sama dengan SKS mata kuliah, input 'beban_sks' dari request diabaikan jika ada.
                $dataToCreate['beban_sks'] = $sks_matakuliah;
            }
            // Validasi 2 -> Menghitung maksimal sks mengajar seorang dosen jika memiliki jabatan struktural
            $dosenPengajar = Dosen::find($validatedData['id_dosen']);
            $konversi_sks_jabatan = 0;
            if ($dosenPengajar->id_jabatan_struktural !== null) {
                $konversi_sks_jabatan = (int)$dosenPengajar->jabatanStruktural->konversi_sks;
                // DEBUG
                // return response()->json([
                //     'success' => true,
                //     'message' => 'Jabatan Struktural.',
                //     'sks_jabatan' => $konversi_sks_jabatan
                // ], 201);
                // DEBUG
            }

            // Validasi 3 -> Menghitung maksimal sks mengajar seorang dosen

            $id_tahun_ajaran = (int)$mapping_matkul->id_tahun_ajaran;
            $maksimalSksDosen = 16;
            $totalSksMengajarDosen = $dosenPengajar->getTotalSksMengajarPadaTahunAjaran($id_tahun_ajaran);
            // $totalSksMengajarDosen = $dosenPengajar->getTotalSKS($id_tahun_ajaran);

            // DEBUG
            // return response()->json([
            //     'success' => true,
            //     'message' => 'Total SKS',
            //     'total sks saat ini' => $totalSksMengajarDosen
            // ], 201);
            // DEBUG

            $total_sks_proyeksi = $konversi_sks_jabatan + $totalSksMengajarDosen + $input_beban_sks;

            // DEBUG
            // return response()->json([
            //     'success' => true,
            //     'message' => 'Total SKS',
            //     'total sks saat ini' => $totalSksMengajarDosen,
            //     'total proyeksi' => $total_sks_proyeksi
            // ], 201);
            // DEBUG

            if ($total_sks_proyeksi > $maksimalSksDosen) {
                return response()->json([
                    'success' => false,
                    'message' => 'Plottingan gagal: Total SKS dosen akan melebihi batas maksimal (' . $maksimalSksDosen . ' SKS). Proyeksi: ' . $total_sks_proyeksi . ' SKS (Jabatan: ' . $konversi_sks_jabatan . ' SKS + Mengajar Sebelumnya: ' . $totalSksMengajarDosen . ' SKS + Plottingan Ini: ' . $input_beban_sks . ' SKS).',
                    'errors' => [
                        'id_dosen' => ['Total SKS dosen akan melebihi batas maksimal.']
                    ]
                ], 422); // Mengembalikan error 422 Unprocessable Entity
            }

            $plottingan = PlottinganPengajaran::create($dataToCreate);

            $plottingan->load(['dosen:id,name', 'mappingKelasMatakuliah.matakuliah:id,nama_matakuliah,kode_matkul,sks']);

            return response()->json([
                'success' => true,
                'message' => 'Plottingan pengajaran berhasil disimpan.',
                'data' => $plottingan
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error during plottingan creation: ' . $e->getMessage(), ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating plottingan pengajaran: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan pada server saat menyimpan data.',
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(PlottinganPengajaran $plottinganPengajaran)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PlottinganPengajaran $plottinganPengajaran)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PlottinganPengajaran $plottinganPengajaran)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PlottinganPengajaran $plottinganPengajaran)
    {
        //
    }
}
