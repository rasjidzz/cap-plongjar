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
use App\Models\TahunAjaran;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PlottinganPengajaranExport;
use Illuminate\Pagination\LengthAwarePaginator;

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

    public function getHasilPlottinganPengajaranByTahunAjaranId($id_tahun_ajaran)
    {
        // Validasi apakah tahun ajaran ada (opsional tapi baik)
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
                $query->select([ // Pilih kolom spesifik dari mapping_kelas_matakuliahs
                    'id',
                    'id_matakuliah',
                    'id_tahun_ajaran',
                    'nama_kelas',
                    'kuota',
                    'team_teaching' // Kolom team_teaching dari mapping_kelas_matakuliahs
                ])
                    ->with([
                        'matakuliah' => function ($matakuliahQuery) {
                            // Pilih kolom spesifik dari matakuliahs
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
                                // 'tingkat_matakuliah', 'hour_target', 'matakuliah_eksepsi' // Jika ada
                            ])->with('pic:id,name'); // Relasi pic dari matakuliah
                        },
                        'tahunAjaran:id,tahun_ajaran,semester', // Relasi tahunAjaran dari mapping
                        'koordinatorMatakuliah' => function ($kmQuery) {
                            // Relasi koordinatorMatakuliah dari mapping
                            $kmQuery->select(['id', 'id_dosen', 'id_mapping_kelas_matakuliah'])
                                ->with('dosen:id,name,lecturer_code'); // Dosen koordinator
                        }
                    ]);
            }
        ])
            ->whereHas('mappingKelasMatakuliah', function ($query) use ($id_tahun_ajaran) {
                // Filter PlottinganPengajaran berdasarkan id_tahun_ajaran di MappingKelasMatakuliah
                $query->where('id_tahun_ajaran', $id_tahun_ajaran);
            })
            ->get();

        // Transformasi data ke format yang diinginkan
        $formattedData = $plottinganItems->map(function ($plot) {
            $mkm = $plot->mappingKelasMatakuliah;
            $matakuliah = $mkm ? $mkm->matakuliah : null;
            $pic = $matakuliah ? $matakuliah->pic : null;
            $dosenPengajar = $plot->dosen;
            $koordinatorPlot = $mkm ? $mkm->koordinatorMatakuliah : null;
            $dosenKoordinator = $koordinatorPlot ? $koordinatorPlot->dosen : null;
            $tahunAjaranInfo = $mkm ? $mkm->tahunAjaran : null;

            return [
                // 'id_plottingan'                 => $plot->id, // ID dari plottingan itu sendiri
                // 'id_mapping_kelas_matakuliah'   => $mkm ? $mkm->id : null,
                // 'id_matakuliah'                 => $matakuliah ? $matakuliah->id : null,
                // 'id_dosen_pengajar'             => $dosenPengajar ? $dosenPengajar->id : null, // Tambahan: ID dosen pengajar
                // 'nama_dosen_pengajar'           => $dosenPengajar ? $dosenPengajar->name : null, // Tambahan: nama dosen pengajar
                // 'sks_kredit'                    => $matakuliah ? $matakuliah->sks : null,
                // 'kuota_kelas'                   => $mkm ? $mkm->kuota : null, // Tambahan: kuota kelas
                // 'id_dosen_koordinator'          => $dosenKoordinator ? $dosenKoordinator->id : null, // Tambahan: ID dosen koordinator
                // 'nama_dosen_koordinator'        => $dosenKoordinator ? $dosenKoordinator->name : null, // Tambahan: nama dosen koordinator
                'kode_matakuliah'                   => $matakuliah ? $matakuliah->kode_matkul : null, // Tambahan: kode matkul
                'nama_matakuliah'               => $matakuliah ? $matakuliah->nama_matakuliah : null,
                'pic'                      => $pic ? $pic->name : null,
                'kode_dosen_pengajar'           => $dosenPengajar ? $dosenPengajar->lecturer_code : null,
                'mandatory_status'              => $matakuliah ? $matakuliah->mandatory_status : null,
                'tingkat_matakuliah'            => $matakuliah->tingkat_matakuliah ?? null, // Placeholder, ganti jika ada fieldnya
                'beban_sks_dosen_pengajar'      => $plot->beban_sks, // Tambahan: SKS yang dibebankan ke dosen ini
                'nama_kelas'                    => $mkm ? $mkm->nama_kelas : null,
                'praktikum'                     => $matakuliah ? ($matakuliah->praktikum ? 'Yes' : 'No') : null,
                'kode_dosen_koordinator'        => $dosenKoordinator ? $dosenKoordinator->lecturer_code : null,
                'hour_target'                   => $matakuliah->hour_target ?? null, // Placeholder, ganti jika ada fieldnya
                'tahun_ajaran'          => $tahunAjaranInfo ? ($tahunAjaranInfo->tahun_ajaran . ' - ' . $tahunAjaranInfo->semester) : null,
                'team_teaching_kelas'           => $mkm ? ($mkm->team_teaching ? 'Yes' : 'No') : null,
                'matakuliah_eksepsi'            => $matakuliah->matakuliah_eksepsi ?? null, // Placeholder, ganti jika ada fieldnya
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
        // Validasi apakah tahun ajaran ada (opsional tapi baik)
        $tahunAjaran = TahunAjaran::find($id_tahun_ajaran);
        if (!$tahunAjaran) {
            // Anda bisa mengembalikan error 404 atau pesan lain jika tahun ajaran tidak ditemukan
            // Untuk export, biasanya lebih baik menghentikan proses jika data sumber tidak valid
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

    // public function getBebanSksDosenByIdDosenandActiveTahunAjaran($id_dosen) {}

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
