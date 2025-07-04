<?php

namespace App\Http\Controllers;

use App\Exports\HasilPlottinganExport;
use App\Models\MappingKelasMatakuliah;
use App\Models\Matakuliah;
use App\Models\PlottinganPengajaran;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use App\Models\Dosen;
use App\Models\KelompokKeahlian;
use App\Models\ProgramStudi;
use App\Models\TahunAjaran;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PlottinganPengajaranExport;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

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
            'beban_sks' => 'nullable|integer|min:1',
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

            $matakuliah = Matakuliah::with('pic')->find($mapping_matkul->id_matakuliah);

            if (!$matakuliah) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mata Kuliah terkait dengan mapping tidak ditemukan.',
                ], 404);
            }

            // Proses Validasi Otorisasi Role

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
                if ($role->id == 2) { // Role ProgramStudi
                    $userHasRelevantRole = true;
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
                } elseif ($role->id == 3) {
                    $userHasRelevantRole = true;
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

            if ($userHasRelevantRole && !$isAuthorized) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak berwenang melakukan plotting untuk mata kuliah dengan PIC (' . $picName . '). Program Studi/Kelompok Keahlian Anda tidak sesuai.',
                ], 403);
            }

            if (!$isAuthorized && $userHasRelevantRole) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak berwenang melakukan plotting untuk mata kuliah dengan PIC (' . $picName . '). Program Studi/Kelompok Keahlian Anda tidak sesuai.',
                ], 403);
            }

            $isSuperAdmin = false;
            foreach ($user->roles as $role) {
                if ($role->id == 1) {
                    $isSuperAdmin = true;
                    break;
                }
            }

            if (!$isSuperAdmin && !$isAuthorized && $userHasRelevantRole) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak berwenang melakukan plotting untuk mata kuliah dengan PIC (' . $picName . '). Program Studi/Kelompok Keahlian Anda tidak sesuai.',
                ], 403);
            } elseif (!$isSuperAdmin && !$userHasRelevantRole) {
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
                if (!$request->filled('beban_sks')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Beban SKS wajib diisi untuk mode team teaching.',
                        'errors' => [
                            'beban_sks' => ['Beban SKS wajib diisi untuk team teaching.']
                        ]
                    ], 422);
                }
                $input_beban_sks = (int)$request->input('beban_sks');

                if ($input_beban_sks > $sks_matakuliah) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Beban SKS yang diinput (' . $input_beban_sks . ') untuk dosen ini tidak boleh melebihi total SKS mata kuliah (' . $sks_matakuliah . ').',
                        'errors' => [
                            'beban_sks' => ['Beban SKS input melebihi SKS mata kuliah.']
                        ]
                    ], 422);
                }

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
                    ], 422);
                }

                $dataToCreate['beban_sks'] = $sks_matakuliah;
            }
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

            $id_tahun_ajaran = (int)$mapping_matkul->id_tahun_ajaran;
            $maksimalSksDosen = 16;
            $totalSksMengajarDosen = $dosenPengajar->getTotalSksMengajarPadaTahunAjaran($id_tahun_ajaran);

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
                ], 422);
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

    public function getHasilPlottinganPengajaranByTahunAjaranId($id_tahun_ajaran)
    {
        $tahunAjaranExists = TahunAjaran::find($id_tahun_ajaran);
        if (!$tahunAjaranExists) {
            return response()->json([
                'success' => false,
                'message' => 'Tahun Ajaran tidak ditemukan.',
                'data' => []
            ], 404);
        }

        $plottinganItems = PlottinganPengajaran::with([
            'dosen:id,name,lecturer_code',
            'mappingKelasMatakuliah' => function ($query) {
                $query->select([
                    'id',
                    'id_matakuliah',
                    'id_tahun_ajaran',
                    'nama_kelas',
                    'kuota',
                    'team_teaching'
                ])
                    ->with([
                        'matakuliah' => function ($matakuliahQuery) {
                            $matakuliahQuery->select([
                                'id',
                                'nama_matakuliah',
                                'kode_matkul',
                                'sks',
                                'praktikum',
                                'id_pic',
                                'mandatory_status',
                                'tingkat_matakuliah',
                                'hour_target',
                                'matakuliah_eksepsi'
                            ])->with('pic:id,name');
                        },
                        'tahunAjaran:id,tahun_ajaran,semester',
                        'koordinatorMatakuliah' => function ($kmQuery) {
                            $kmQuery->select(['id', 'id_dosen', 'id_mapping_kelas_matakuliah'])
                                ->with('dosen:id,name,lecturer_code');
                        }
                    ]);
            }
        ])
            ->whereHas('mappingKelasMatakuliah', function ($query) use ($id_tahun_ajaran) {
                $query->where('id_tahun_ajaran', $id_tahun_ajaran);
            })
            ->get();

        $formattedData = $plottinganItems->map(function ($plot) {
            $mkm = $plot->mappingKelasMatakuliah;
            $matakuliah = $mkm ? $mkm->matakuliah : null;
            $pic = $matakuliah ? $matakuliah->pic : null;
            $dosenPengajar = $plot->dosen;
            $koordinatorPlot = $mkm ? $mkm->koordinatorMatakuliah : null;
            $dosenKoordinator = $koordinatorPlot ? $koordinatorPlot->dosen : null;
            $tahunAjaranInfo = $mkm ? $mkm->tahunAjaran : null;

            return [
                'kode_matakuliah'                   => $matakuliah ? $matakuliah->kode_matkul : null,
                'nama_matakuliah'               => $matakuliah ? $matakuliah->nama_matakuliah : null,
                'pic'                      => $pic ? $pic->name : null,
                'kode_dosen_pengajar'           => $dosenPengajar ? $dosenPengajar->lecturer_code : null,
                'mandatory_status'              => $matakuliah ? $matakuliah->mandatory_status : null,
                'tingkat_matakuliah'            => $matakuliah->tingkat_matakuliah ?? null,
                'beban_sks_dosen_pengajar'      => $plot->beban_sks,
                'nama_kelas'                    => $mkm ? $mkm->nama_kelas : null,
                'praktikum'                     => $matakuliah ? ($matakuliah->praktikum ? 'Yes' : 'No') : null,
                'kode_dosen_koordinator'        => $dosenKoordinator ? $dosenKoordinator->lecturer_code : null,
                'hour_target'                   => $matakuliah->hour_target ?? null,
                'tahun_ajaran'          => $tahunAjaranInfo ? ($tahunAjaranInfo->tahun_ajaran . ' - ' . $tahunAjaranInfo->semester) : null,
                'team_teaching_kelas'           => $mkm ? ($mkm->team_teaching ? 'Yes' : 'No') : null,
                'matakuliah_eksepsi'            => $matakuliah->matakuliah_eksepsi ?? null,
            ];
        });

        if ($formattedData->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'Tidak ada data plottingan pengajaran yang ditemukan untuk tahun ajaran ini.',
                'data' => []
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Hasil Plottingan Pengajaran berhasil dimuat.',
            'data' => $formattedData
        ]);

        // $rawData = PlottinganPengajaran; -> ambil data dari Model Plottingan Pnegajaran Relasi ke Tabel Mapping_kelas_matakuliah, matakuliah, pic, koordinatorMatakuliah
        // $data = [
        //     'id_matakuliah',
        //     'nama_matakuliah',
        //     'nama_pic',
        //     'kode_dosen',
        //     'mandatory_status',
        //     'tingkat_matakuliah',
        //     'sks/kredit',
        //     'nama_kelas',
        //     'praktikum',
        //     'kode_dosen_koordinator',
        //     'tahun_ajaran',
        //     'hour_target',
        //     'team_teaching',
        //     'matakuliah_eksepsi'
        // ];
    }

    public function exportHasilPlottinganToExcel($id_tahun_ajaran)
    {
        $tahunAjaran = TahunAjaran::find($id_tahun_ajaran);
        if (!$tahunAjaran) {
            abort(404, 'Tahun Ajaran tidak ditemukan.');
        }

        $fileName = 'hasil_plottingan_pengajaran_' . str_replace('/', '-', $tahunAjaran->tahun_ajaran) . '_' . $tahunAjaran->semester . '.xlsx';

        return Excel::download(new PlottinganPengajaranExport((int)$id_tahun_ajaran), $fileName);
    }

    // public function getHasilPlottinganByProdiDanTahunAjaran($id_tahun_ajaran, $id_program_studi)
    // {
    //     // 1. Validasi apakah parameter ada
    //     $tahunAjaran = TahunAjaran::find($id_tahun_ajaran);
    //     if (!$tahunAjaran) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Tahun Ajaran tidak ditemukan.',
    //         ], 404);
    //     }

    //     $programStudi = ProgramStudi::find($id_program_studi);
    //     if (!$programStudi) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Program Studi tidak ditemukan.',
    //         ], 404);
    //     }

    //     // 2. Ambil data plottingan dengan filter dan relasi
    //     $plottinganItems = PlottinganPengajaran::with([
    //         'dosen:id,name,lecturer_code',
    //         'mappingKelasMatakuliah' => function ($query) {
    //             $query->with([
    //                 'matakuliah' => function ($matakuliahQuery) {
    //                     $matakuliahQuery->with('pic:id,name');
    //                 },
    //                 'tahunAjaran:id,tahun_ajaran,semester', // Pastikan relasi tahunAjaran di-load
    //                 'koordinatorMatakuliah' => function ($kmQuery) {
    //                     $kmQuery->with('dosen:id,name,lecturer_code');
    //                 }
    //             ]);
    //         }
    //     ])
    //         ->whereHas('mappingKelasMatakuliah', function ($query) use ($id_tahun_ajaran, $id_program_studi) {
    //             // Filter utama berdasarkan tahun ajaran dan program studi
    //             $query->where('id_tahun_ajaran', $id_tahun_ajaran)
    //                 ->where('id_program_studi', $id_program_studi);
    //         })
    //         ->get();

    //     // 3. Transformasi data ke format yang diinginkan
    //     $formattedData = $plottinganItems->map(function ($plot) {
    //         $mkm = $plot->mappingKelasMatakuliah;
    //         $matakuliah = $mkm ? $mkm->matakuliah : null;
    //         $dosenPengajar = $plot->dosen;
    //         $dosenKoordinator = $mkm?->koordinatorMatakuliah?->dosen;
    //         $tahunAjaranInfo = $mkm ? $mkm->tahunAjaran : null;

    //         return [
    //             'id_plottingan'             => $plot->id,
    //             'id_mapping_kelas'          => $mkm?->id,
    //             'nama_matakuliah'           => $matakuliah?->nama_matakuliah,
    //             'kode_matakuliah'           => $matakuliah?->kode_matkul,
    //             'pic_matakuliah'            => $matakuliah?->pic?->name,
    //             'sks_matakuliah'            => $matakuliah?->sks,
    //             'nama_kelas'                => $mkm?->nama_kelas,
    //             'dosen_pengajar'            => $dosenPengajar?->name,
    //             'kode_dosen_pengajar'       => $dosenPengajar?->lecturer_code,
    //             'beban_sks_dosen'           => $plot->beban_sks,
    //             'koordinator_matakuliah'    => $dosenKoordinator?->name,
    //             'kode_koordinator'          => $dosenKoordinator?->lecturer_code,
    //             'tahun_ajaran'              => $tahunAjaranInfo ? ($tahunAjaranInfo->tahun_ajaran . ' - ' . $tahunAjaranInfo->semester) : null,
    //             'mandatory_status'          => $matakuliah?->mandatory_status,
    //             'tingkat_matakuliah'        => $matakuliah?->tingkat_matakuliah,
    //             'hour_target'               => $matakuliah?->hour_target,
    //             'mk_eksepsi'                => $matakuliah?->matakuliah_eksepsi,
    //         ];
    //     });

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Hasil Plottingan Pengajaran untuk Program Studi "' . $programStudi->name . '" pada Tahun Ajaran "' . $tahunAjaran->tahun_ajaran . ' - ' . $tahunAjaran->semester . '" berhasil dimuat.',
    //         'data' => $formattedData
    //     ]);
    // }

    public function getHasilPlottinganByProdiDanTahunAjaran(Request $request, $id_tahun_ajaran, $id_program_studi)
    {
        // 1. Validasi apakah parameter ada
        $tahunAjaran = TahunAjaran::find($id_tahun_ajaran);
        if (!$tahunAjaran) {
            return response()->json([
                'success' => false,
                'message' => 'Tahun Ajaran tidak ditemukan.',
            ], 404);
        }

        $programStudi = ProgramStudi::find($id_program_studi);
        if (!$programStudi) {
            return response()->json([
                'success' => false,
                'message' => 'Program Studi tidak ditemukan.',
            ], 404);
        }

        // Ambil parameter paginasi
        $perPage = $request->query('per_page', 12);

        // 2. Ambil data plottingan dengan filter, relasi, dan paginasi
        $plottinganItems = PlottinganPengajaran::with([
            'dosen:id,name,lecturer_code',
            'mappingKelasMatakuliah' => function ($query) {
                $query->with([
                    'matakuliah' => function ($matakuliahQuery) {
                        $matakuliahQuery->with('pic:id,name');
                    },
                    'tahunAjaran:id,tahun_ajaran,semester',
                    'koordinatorMatakuliah' => function ($kmQuery) {
                        $kmQuery->with('dosen:id,name,lecturer_code');
                    }
                ]);
            }
        ])
            ->whereHas('mappingKelasMatakuliah', function ($query) use ($id_tahun_ajaran, $id_program_studi) {
                // Filter utama berdasarkan tahun ajaran dan program studi
                $query->where('id_tahun_ajaran', $id_tahun_ajaran)
                    ->where('id_program_studi', $id_program_studi);
            })
            ->paginate($perPage);

        // 3. Transformasi data pada koleksi halaman saat ini
        $formattedData = $plottinganItems->getCollection()->map(function ($plot) {
            $mkm = $plot->mappingKelasMatakuliah;
            $matakuliah = $mkm ? $mkm->matakuliah : null;
            $dosenPengajar = $plot->dosen;
            $dosenKoordinator = $mkm?->koordinatorMatakuliah?->dosen;
            $tahunAjaranInfo = $mkm ? $mkm->tahunAjaran : null;

            return [
                'id_plottingan'             => $plot->id,
                'id_mapping_kelas'          => $mkm?->id,
                'nama_matakuliah'           => $matakuliah?->nama_matakuliah,
                'kode_matakuliah'           => $matakuliah?->kode_matkul,
                'pic_matakuliah'            => $matakuliah?->pic?->name,
                'sks_matakuliah'            => $matakuliah?->sks,
                'nama_kelas'                => $mkm?->nama_kelas,
                'dosen_pengajar'            => $dosenPengajar?->name,
                'kode_dosen_pengajar'       => $dosenPengajar?->lecturer_code,
                'beban_sks_dosen'           => $plot->beban_sks,
                'koordinator_matakuliah'    => $dosenKoordinator?->name,
                'kode_koordinator'          => $dosenKoordinator?->lecturer_code,
                'tahun_ajaran'              => $tahunAjaranInfo ? ($tahunAjaranInfo->tahun_ajaran . ' - ' . $tahunAjaranInfo->semester) : null,
                'mandatory_status'          => $matakuliah?->mandatory_status,
                'tingkat_matakuliah'        => $matakuliah?->tingkat_matakuliah,
                'hour_target'               => $matakuliah?->hour_target,
                'mk_eksepsi'                => $matakuliah?->matakuliah_eksepsi,
                'team_teaching'             => $mkm ? ($mkm->team_teaching ? 'Yes' : 'No') : null,
            ];
        });

        // Buat instance Paginator baru dengan data yang sudah ditransformasi
        $paginatedResponse = new LengthAwarePaginator(
            $formattedData,
            $plottinganItems->total(),
            $plottinganItems->perPage(),
            $plottinganItems->currentPage(),
            ['path' => $request->url(), 'query' => $request->query()]
        );


        return response()->json([
            'success' => true,
            'message' => 'Hasil Plottingan Pengajaran untuk Program Studi "' . $programStudi->nama . '" pada Tahun Ajaran "' . $tahunAjaran->tahun_ajaran . ' - ' . $tahunAjaran->semester . '" berhasil dimuat.',
            'data' => $paginatedResponse
        ]);
    }

    public function exportHasilPlottinganByProdiDanTahunAjaranToExcel($id_tahun_ajaran, $id_program_studi)
    {
        // Validasi sederhana
        $tahunAjaran = TahunAjaran::find($id_tahun_ajaran);
        $programStudi = ProgramStudi::find($id_program_studi);

        if (!$tahunAjaran || !$programStudi) {
            abort(404, 'Tahun ajaran atau program studi tidak ditemukan.');
        }

        // Buat nama file yang dinamis
        $fileName = 'hasil_plottingan_prodi_'
            . str_replace('', '_', $programStudi->nama) . '_'
            . str_replace('/', '-', $tahunAjaran->tahun_ajaran) . '_'
            . $tahunAjaran->semester . '.xlsx';
        // . str_replace(' ', '_', $programStudi->nama) . '.xlsx';

        // Panggil class Export dan picu download
        return Excel::download(new HasilPlottinganExport((int)$id_tahun_ajaran, (int)$id_program_studi), $fileName);
    }

    public function unassignPlottingan(PlottinganPengajaran $plottinganPengajaran)
    {
        try {
            // Melakukan soft delete. Eloquent akan otomatis mengisi kolom 'deleted_at'.
            $plottinganPengajaran->delete();

            return response()->json([
                'success' => true,
                'message' => 'Plottingan pengajaran berhasil dihapus (un-assigned).'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus plottingan karena terjadi kesalahan pada server.'
            ], 500);
        }
    }
    public function getPlottinganSummary(Request $request)
    {
        $request->validate([
            'id_program_studi' => 'nullable|integer|exists:program_studis,id',
            'id_tahun_ajaran' => 'nullable|integer|exists:tahun_ajarans,id',
        ]);

        $id_program_studi_filter = $request->query('id_program_studi');
        $id_tahun_ajaran_filter = $request->query('id_tahun_ajaran');
        $perPage = $request->query('per_page', 15);

        $query = DB::table('mapping_kelas_matakuliahs as mkm')
            ->join('plottingan_pengajarans as pp', 'mkm.id', '=', 'pp.id_mapping_kelas_matakuliah')
            ->join('program_studis as ps', 'mkm.id_program_studi', '=', 'ps.id')
            ->join('tahun_ajarans as ta', 'mkm.id_tahun_ajaran', '=', 'ta.id')
            ->select(
                'ps.id as id_program_studi',
                'ps.nama as nama_program_studi',
                'ta.id as id_tahun_ajaran',
                'ta.tahun_ajaran',
                'ta.semester'
            )
            ->distinct();

        $query->when($id_program_studi_filter, function ($q) use ($id_program_studi_filter) {
            $q->where('ps.id', $id_program_studi_filter);
        });

        $query->when($id_tahun_ajaran_filter, function ($q) use ($id_tahun_ajaran_filter) {
            $q->where('ta.id', $id_tahun_ajaran_filter);
        });

        $summaries = $query->orderBy('ps.nama', 'asc')
            ->orderBy('ta.tahun_ajaran', 'desc')
            ->orderBy('ta.semester', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Ringkasan plottingan berhasil dimuat.',
            'data' => $summaries
        ]);
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
