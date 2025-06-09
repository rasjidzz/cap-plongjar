<?php

namespace App\Http\Controllers;

use App\Models\Dosen;
use App\Models\PlottinganPengajaran;
use App\Models\ProgramStudi;
use App\Models\TahunAjaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Pagination\LengthAwarePaginator;
// use App\Http\Controllers\Log;

class DosenController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = Dosen::with('kelompokKeahlian')->get();
        return response()->json([
            'success' => true,
            'message' => 'List All Dosen Data',
            'data' => $data
        ]);
    }
    public function getAllDosen(Request $request)
    {
        $query = Dosen::with('kelompokKeahlian:id,nama')
            ->select('id', 'name', 'lecturer_code', 'nip', 'status_pegawai', 'id_kelompok_keahlian')
            ->when($request->search, function ($q) use ($request) {
                $q->where(function ($subQuery) use ($request) {
                    $subQuery->where('name', 'like', '%' . $request->search . '%')
                        ->orWhere('lecturer_code', 'like', '%' . $request->search . '%')
                        ->orWhere('nip', 'like', '%' . $request->search . '%');
                });
            });

        // Default pagination: 10 per page, bisa dicustom lewat query param ?per_page=
        $data = $query->paginate($request->get('per_page', 10));

        return response()->json([
            'success' => true,
            'message' => 'List All Dosen (filtered & paginated)',
            'data' => $data
        ]);
    }
    public function getAllDosenByKKId($id_kk)
    {
        $data = Dosen::with('kelompokKeahlian:id,nama')
            ->select('id', 'name', 'lecturer_code', 'nip', 'status_pegawai', 'id_kelompok_keahlian')
            ->where('id_kelompok_keahlian', $id_kk)
            ->get();
        return response()->json([
            'success' => true,
            'message' => 'List All Dosen Data (id, name, lecturer_code, nip, kelompok_keahlian, status_pegawai)',
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
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'lecturer_code' => 'required|string|max:3',
            'email' => 'required|email|unique:dosens,email',
            'jabatan_fungsional_akademik' => 'required|in:Asisten Ahli,Lektor,Lektor Kepala,Guru Besar',
            'status_pegawai' => 'required|in:Dosen Perbantuan Kopertis,Dosen Perbantuan Telkom,Dosen Profesional (full time),Dosen Profesional (part time),Pegawai Tetap',
            'pendidikan_terakhir' => 'required|in:SMA,S-1,S-2,S-3',
            'nidn' => 'nullable|string|max:100',
            'id_kelompok_keahlian' => 'required|exists:kelompok_keahlians,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $dosen = Dosen::create([
            'name' => $request->name,
            'lecturer_code' => $request->lecturer_code,
            'email' => $request->email,
            'jabatan_fungsional_akademik' => $request->jabatan_fungsional_akademik,
            'status_pegawai' => $request->status_pegawai,
            'pendidikan_terakhir' => $request->pendidikan_terakhir,
            'nidn' => $request->nidn,
            'id_kelompok_keahlian' => $request->id_kelompok_keahlian,
        ]);

        return response()->json([
            'message' => 'Data dosen berhasil ditambahkan.',
            'data' => $dosen
        ], 201);
    }
    // public function getDosenDetailData($id_dosen)
    // {
    //     $data = Dosen::with('kelompokKeahlian:id,nama')
    //         ->select('id', 'name', 'lecturer_code', 'nip', 'status_pegawai', 'id_kelompok_keahlian')
    //         ->where('id', $id_dosen)
    //         ->get();
    //     return response()->json([
    //         'success' => true,
    //         'message' => 'List All Dosen Data (id, name, lecturer_code, nip, kelompok_keahlian, status_pegawai)',
    //         'data' => $data
    //     ]);
    // }

    // public function getDosenDetailData($id_dosen)
    // {
    //     $dosen = Dosen::with('kelompokKeahlian:id,nama')
    //         ->select(
    //             'id',
    //             'name',
    //             'lecturer_code',
    //             'id_jabatan_struktural',
    //             'nip',
    //             'nidn',
    //             'id_kelompok_keahlian',
    //             'status_pegawai',
    //             'email',
    //             'jabatan_fungsional_akademik',
    //             'pendidikan_terakhir'
    //         )
    //         ->where('id', $id_dosen)
    //         ->first();

    //     if (!$dosen) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Dosen tidak ditemukan.',
    //             'data' => null
    //         ], 404);
    //     }

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Detail Data Dosen',
    //         'data' => [
    //             'nama_dosen' => $dosen->name,
    //             'kode_dosen' => $dosen->lecturer_code,
    //             'jabatan' => $dosen->id_jabatan_struktural,
    //             'home_base' => null,
    //             'nip' => $dosen->nip,
    //             'nidn' => $dosen->nidn,
    //             'bidang_keahlian' => $dosen->kelompokKeahlian?->nama,
    //             'status' => $dosen->status_pegawai,
    //             'contact_person' => $dosen->email,
    //             'jfa' => $dosen->jabatan_fungsional_akademik,
    //             'riwayat_pengajaran' => url("/riwayat-mengajar/{$dosen->id}"),
    //             'pendidikan' => $dosen->pendidikan_terakhir,
    //         ]
    //     ]);
    // }
    public function getDosenDetailData($id_dosen)
    {
        $dosen = Dosen::with([
            'kelompokKeahlian:id,nama',
            'jabatanStruktural:id,nama'
        ])
            ->select(
                'id',
                'name',
                'lecturer_code',
                'id_jabatan_struktural',
                'nip',
                'nidn',
                'id_kelompok_keahlian',
                'status_pegawai',
                'email',
                'jabatan_fungsional_akademik',
                'pendidikan_terakhir'
            )
            ->where('id', $id_dosen)
            ->first();

        if (!$dosen) {
            return response()->json([
                'success' => false,
                'message' => 'Dosen tidak ditemukan.',
                'data' => null
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail Data Dosen',
            'data' => [
                'nama_dosen' => $dosen->name,
                'kode_dosen' => $dosen->lecturer_code,
                'jabatan' => $dosen->jabatanStruktural?->nama, // gunakan nama dari relasi
                'home_base' => null,
                'nip' => $dosen->nip,
                'nidn' => $dosen->nidn,
                'bidang_keahlian' => $dosen->kelompokKeahlian?->nama,
                'status' => $dosen->status_pegawai,
                'contact_person' => $dosen->email,
                'jfa' => $dosen->jabatan_fungsional_akademik,
                'riwayat_pengajaran' => url("/riwayat-mengajar/{$dosen->id}"),
                'pendidikan' => $dosen->pendidikan_terakhir,
            ]
        ]);
    }

    public function assignJabatanStruktural(Request $request)
    {
        // Validasi input
        $validatedData = $request->validate([
            'id_dosen' => 'required|exists:dosens,id',
            'id_jabatan_struktural' => 'required|exists:jabatan_strukturals,id',
        ]);

        try {
            $dosen = Dosen::find($validatedData['id_dosen']);

            if (!$dosen) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dosen tidak ditemukan.',
                ], 404);
            }

            $dosen->id_jabatan_struktural = $validatedData['id_jabatan_struktural'];
            $dosen->save();

            $message = 'Jabatan Struktural berhasil di-assign ke Dosen.';

            return response()->json([
                'success' => true,
                'message' => $message,
                // 'data' => $dosen->load('jabatanStruktural'), // Muat relasi untuk respons
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            // Log::error('Error assigning Jabatan Struktural to Dosen: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan pada server.',
                'error Message' => $e
            ], 500);
        }
    }
    public function revokeJabatanStrukturalDosen(Request $request)
    {
        $validatedData = $request->validate([
            'id_dosen' => 'required|exists:dosens,id',
        ]);

        try {
            $dosen = Dosen::find($validatedData['id_dosen']);

            if (!$dosen) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dosen tidak ditemukan.',
                ], 404);
            }

            $dosen->id_jabatan_struktural = null;
            $dosen->save();

            return response()->json([
                'success' => true,
                'message' => 'Jabatan Struktural Dosen berhasil dilepas/direvoke.',
                'data' => $dosen,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            // Log::error('Error revoking Jabatan Struktural Dosen: ' . $e->getMessage() . ' | File: ' . $e->getFile() . ' | Line: ' . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan pada server saat melepas jabatan.',
            ], 500);
        }
    }

    // public function getLaporanBebanSksDosen($id_tahun_ajaran)
    // {
    //     // Validasi apakah tahun ajaran ada
    //     $tahunAjaran = TahunAjaran::find($id_tahun_ajaran);
    //     if (!$tahunAjaran) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Tahun Ajaran tidak ditemukan.',
    //             'data' => []
    //         ], 404);
    //     }

    //     // Ambil semua dosen beserta relasi yang dibutuhkan
    //     $dosens = Dosen::with(['kelompokKeahlian:id,nama', 'jabatanStruktural:id,nama,konversi_sks'])
    //         ->orderBy('name', 'asc') // Urutkan berdasarkan nama dosen
    //         ->get();

    //     // Ambil semua program studi untuk iterasi
    //     $programStudis = ProgramStudi::select(['id', 'nama'])->get();

    //     $maksimalSksMengajarDefault = 16; // Batas SKS mengajar normal

    //     $laporanData = $dosens->map(function ($dosen) use ($id_tahun_ajaran, $programStudis, $maksimalSksMengajarDefault) {
    //         $konversi_sks_jabatan = 0;
    //         $nama_jabatan_struktural = null;

    //         if ($dosen->jabatanStruktural) {
    //             $konversi_sks_jabatan = (int)$dosen->jabatanStruktural->konversi_sks;
    //             $nama_jabatan_struktural = $dosen->jabatanStruktural->nama;
    //         }

    //         $maxAjarSks = $maksimalSksMengajarDefault - $konversi_sks_jabatan;
    //         // Pastikan maxAjarSks tidak negatif
    //         $maxAjarSks = $maxAjarSks < 0 ? 0 : $maxAjarSks;

    //         // Hitung total SKS mengajar pada tahun ajaran ini
    //         // Menggunakan method yang sudah ada di model Dosen
    //         $totalAjarSksKeseluruhan = $dosen->getTotalSksMengajarPadaTahunAjaran((int)$id_tahun_ajaran);

    //         // Hitung total SKS mengajar per program studi
    //         $totalAjarPerProdi = [];
    //         foreach ($programStudis as $prodi) {
    //             $sksDiProdiIni = $dosen->plottinganPengajarans()
    //                 ->whereHas('mappingKelasMatakuliah', function ($queryMKM) use ($id_tahun_ajaran, $prodi) {
    //                     $queryMKM->where('id_tahun_ajaran', $id_tahun_ajaran)
    //                         ->where('id_program_studi', $prodi->id);
    //                 })
    //                 ->sum('beban_sks');

    //             // Hanya tambahkan ke array jika SKS > 0 untuk menjaga output tetap bersih
    //             if ($sksDiProdiIni > 0) {
    //                 $totalAjarPerProdi[$prodi->nama] = (int)$sksDiProdiIni;
    //             }
    //         }

    //         return [
    //             'kode_dosen'        => $dosen->lecturer_code,
    //             'nama_dosen'        => $dosen->name,
    //             'kelompok_keahlian' => $dosen->kelompokKeahlian ? $dosen->kelompokKeahlian->nama : null,
    //             'jfa'               => $dosen->jabatan_fungsional_akademik,
    //             'jabatan_struktural' => $nama_jabatan_struktural,
    //             'sks_ekuivalen_jabatan' => $konversi_sks_jabatan, // Tambahan: SKS dari jabatan
    //             'max_ajar_sks'      => $maxAjarSks,
    //             'status_pegawai'    => $dosen->status_pegawai,
    //             'total_ajar_per_prodi' => !empty($totalAjarPerProdi) ? $totalAjarPerProdi : null, // Menampilkan SKS per prodi
    //             'total_ajar_sks_keseluruhan' => $totalAjarSksKeseluruhan,
    //             'sisa_sks_mengajar' => $maxAjarSks - $totalAjarSksKeseluruhan, // Tambahan: Sisa SKS yang bisa diambil
    //         ];
    //     });

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Laporan Beban SKS Dosen untuk Tahun Ajaran ' . $tahunAjaran->tahun_ajaran . ' (' . $tahunAjaran->semester . ') berhasil dimuat.',
    //         'data' => $laporanData
    //     ]);
    // }

    public function getLaporanBebanSksDosen(Request $request, $id_tahun_ajaran)
    {
        // Validasi apakah tahun ajaran ada
        $tahunAjaran = TahunAjaran::find($id_tahun_ajaran);
        if (!$tahunAjaran) {
            return response()->json([
                'success' => false,
                'message' => 'Tahun Ajaran tidak ditemukan.',
                'data' => []
            ], 404);
        }

        // Ambil parameter pencarian dan paginasi dari request
        $searchTerm = $request->query('search', ''); // Untuk mencari nama dosen
        $perPage = $request->query('per_page', 10);  // Jumlah item per halaman, default 15

        // Mulai query builder untuk Dosen
        $dosenQuery = Dosen::with(['kelompokKeahlian:id,nama', 'jabatanStruktural:id,nama,konversi_sks']);

        // Terapkan filter pencarian jika ada searchTerm
        if (!empty($searchTerm)) {
            $dosenQuery->where('name', 'LIKE', "%{$searchTerm}%");
        }

        // Lakukan paginasi pada query dosen
        $paginatedDosens = $dosenQuery->orderBy('name', 'asc')->paginate($perPage);

        // Ambil semua program studi untuk iterasi (tetap diperlukan untuk setiap dosen dalam halaman)
        $programStudis = ProgramStudi::select(['id', 'nama'])->get(); // 'nama' diganti 'name' sesuai contoh data Anda

        $maksimalSksMengajarDefault = 16; // Batas SKS mengajar normal

        // Transformasi data untuk dosen yang ada di halaman saat ini
        $laporanData = $paginatedDosens->getCollection()->map(function ($dosen) use ($id_tahun_ajaran, $programStudis, $maksimalSksMengajarDefault) {
            $konversi_sks_jabatan = 0;
            $nama_jabatan_struktural = null;

            if ($dosen->jabatanStruktural) {
                $konversi_sks_jabatan = (int)$dosen->jabatanStruktural->konversi_sks;
                $nama_jabatan_struktural = $dosen->jabatanStruktural->nama;
            }

            $maxAjarSks = $maksimalSksMengajarDefault - $konversi_sks_jabatan;
            $maxAjarSks = $maxAjarSks < 0 ? 0 : $maxAjarSks;

            $totalAjarSksKeseluruhan = $dosen->getTotalSksMengajarPadaTahunAjaran((int)$id_tahun_ajaran);

            $totalAjarPerProdi = [];
            foreach ($programStudis as $prodi) {
                $sksDiProdiIni = $dosen->plottinganPengajarans()
                    ->whereHas('mappingKelasMatakuliah', function ($queryMKM) use ($id_tahun_ajaran, $prodi) {
                        $queryMKM->where('id_tahun_ajaran', $id_tahun_ajaran)
                            ->where('id_program_studi', $prodi->id);
                    })
                    ->sum('beban_sks');

                if ($sksDiProdiIni > 0) {
                    // Menggunakan $prodi->name (atau $prodi->nama jika itu nama kolomnya)
                    $totalAjarPerProdi[$prodi->nama] = (int)$sksDiProdiIni;
                }
            }

            return [
                'kode_dosen'        => $dosen->lecturer_code,
                'nama_dosen'        => $dosen->name,
                'kelompok_keahlian' => $dosen->kelompokKeahlian ? $dosen->kelompokKeahlian->nama : null, // 'nama' diganti 'name'
                'jfa'               => $dosen->jabatan_fungsional_akademik,
                'jabatan_struktural' => $nama_jabatan_struktural,
                'sks_ekuivalen_jabatan' => $konversi_sks_jabatan,
                'max_ajar_sks'      => $maxAjarSks,
                'status_pegawai'    => $dosen->status_pegawai,
                'total_ajar_per_prodi' => !empty($totalAjarPerProdi) ? $totalAjarPerProdi : null,
                'total_ajar_sks_keseluruhan' => $totalAjarSksKeseluruhan,
                'sisa_sks_mengajar' => $maxAjarSks - $totalAjarSksKeseluruhan,
            ];
        });

        // Membuat respons paginasi manual untuk data yang sudah ditransformasi
        $paginatedResponse = new \Illuminate\Pagination\LengthAwarePaginator(
            $laporanData,
            $paginatedDosens->total(),
            $paginatedDosens->perPage(),
            $paginatedDosens->currentPage(),
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return response()->json([
            'success' => true,
            'message' => 'Laporan Beban SKS Dosen untuk Tahun Ajaran ' . $tahunAjaran->tahun_ajaran . ' (' . $tahunAjaran->semester . ') berhasil dimuat.',
            'data' => $paginatedResponse // Mengembalikan data yang sudah dipaginasi
        ]);
    }

    public function getRiwayatPengajaran(Request $request, $id_dosen)
    {
        // 1. Validasi apakah dosen ada
        $dosen = Dosen::find($id_dosen);
        if (!$dosen) {
            return response()->json([
                'success' => false,
                'message' => 'Dosen tidak ditemukan.',
            ], 404);
        }

        // 2. Ambil semua plottingan untuk dosen ini dengan relasi yang dibutuhkan
        // $riwayatPlottingan = PlottinganPengajaran::with([
        //     'mappingKelasMatakuliah.matakuliah.pic',
        //     'mappingKelasMatakuliah.tahunAjaran'
        // ])
        //     ->where('id_dosen', $id_dosen)
        //     ->get();

        // // 3. Transformasi data ke format yang diinginkan
        // $formattedRiwayat = $riwayatPlottingan->map(function ($plot) {
        //     $matakuliah = $plot->mappingKelasMatakuliah?->matakuliah;
        //     $tahunAjaran = $plot->mappingKelasMatakuliah?->tahunAjaran;

        //     return [
        //         'nama_matakuliah'   => $matakuliah?->nama_matakuliah,
        //         'pic_matakuliah'    => $matakuliah?->pic?->name,
        //         'online_onsite'     => $matakuliah?->mode_perkuliahan,
        //         'kelas'             => $plot->mappingKelasMatakuliah?->nama_kelas,
        //         'kuota'             => $plot->mappingKelasMatakuliah?->kuota,
        //         'periode'           => $tahunAjaran ? ($tahunAjaran->tahun_ajaran . ' - ' . $tahunAjaran->semester) : null,
        //     ];
        // });

        // 4. Kirim respons
        // return response()->json([
        //     'success' => true,
        //     'message' => 'Riwayat pengajaran untuk dosen ' . $dosen->name . ' berhasil dimuat.',
        //     'data' => $formattedRiwayat
        // ]);

        // Ambil parameter pencarian dan paginasi
        $searchTerm = $request->query('search', '');
        $perPage = $request->query('per_page', 15);

        // 2. Mulai query untuk plottingan dosen ini
        $query = PlottinganPengajaran::with([
            'mappingKelasMatakuliah.matakuliah.pic',
            'mappingKelasMatakuliah.tahunAjaran'
        ])
            ->where('id_dosen', $id_dosen);

        // Tambahkan kondisi pencarian jika ada search term
        if (!empty($searchTerm)) {
            $query->whereHas('mappingKelasMatakuliah.matakuliah', function ($matakuliahQuery) use ($searchTerm) {
                $matakuliahQuery->where('nama_matakuliah', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('kode_matkul', 'LIKE', "%{$searchTerm}%");
            });
        }

        // Lakukan paginasi
        $riwayatPlottingan = $query->latest()->paginate($perPage);

        // 3. Transformasi data ke format yang diinginkan
        $formattedRiwayat = $riwayatPlottingan->getCollection()->map(function ($plot) {
            // Menggunakan null-safe operator (?->) untuk keamanan jika ada relasi yang null
            $matakuliah = $plot->mappingKelasMatakuliah?->matakuliah;
            $tahunAjaran = $plot->mappingKelasMatakuliah?->tahunAjaran;

            return [
                'nama_matakuliah'   => $matakuliah?->nama_matakuliah,
                'pic_matakuliah'    => $matakuliah?->pic?->name,
                'online_onsite'     => $matakuliah?->mode_perkuliahan,
                'kelas'             => $plot->mappingKelasMatakuliah?->nama_kelas,
                'kuota'             => $plot->mappingKelasMatakuliah?->kuota,
                'periode'           => $tahunAjaran ? ($tahunAjaran->tahun_ajaran . ' - ' . $tahunAjaran->semester) : null,
            ];
        });

        // Buat instance paginator baru dengan data yang sudah ditransformasi
        $paginatedFormattedData = new LengthAwarePaginator(
            $formattedRiwayat,
            $riwayatPlottingan->total(),
            $riwayatPlottingan->perPage(),
            $riwayatPlottingan->currentPage(),
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // 4. Kirim respons
        return response()->json([
            'success' => true,
            'message' => 'Riwayat pengajaran untuk dosen ' . $dosen->name . ' berhasil dimuat.',
            'data' => $paginatedFormattedData
        ]);
    }

    // public function getBebanSksDosenByIdDosenandActiveTahunAjaran($id_dosen) {}
    public function getBebanSksDosenByIdDosenandActiveTahunAjaran($id_dosen)
    {
        // Langkah 1: Cari tahun ajaran yang aktif
        $tahunAjaranAktif = TahunAjaran::where('status', true)->first();

        if (!$tahunAjaranAktif) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada tahun ajaran yang sedang aktif saat ini.',
            ], 404);
        }

        // Langkah 2: Cari dosen berdasarkan ID dan eager load relasi jabatan
        $dosen = Dosen::with('jabatanStruktural')->find($id_dosen);

        if (!$dosen) {
            return response()->json([
                'success' => false,
                'message' => 'Dosen tidak ditemukan.',
            ], 404);
        }

        // Langkah 3: Lakukan perhitungan SKS
        $konversi_sks_jabatan = 0;
        if ($dosen->jabatanStruktural) {
            $konversi_sks_jabatan = (int)$dosen->jabatanStruktural->konversi_sks;
        }

        $maksimalTotalSks = 16; // Batas total SKS
        $maxAjarSks = $maksimalTotalSks - $konversi_sks_jabatan;
        $maxAjarSks = $maxAjarSks < 0 ? 0 : $maxAjarSks; // Pastikan tidak negatif

        // Panggil method dari model Dosen untuk menghitung SKS mengajar yang sudah diplot
        $totalSksMengajar = $dosen->getTotalSksMengajarPadaTahunAjaran($tahunAjaranAktif->id);

        // Hitung sisa SKS yang bisa diambil
        $sisaSksMengajar = $maxAjarSks - $totalSksMengajar;

        // Opsi 1
        // Langkah 4: Siapkan data untuk respons
        // $responseData = [
        //     'id_dosen' => $dosen->id,
        //     'nama_dosen' => $dosen->name,
        //     'info_tahun_ajaran_aktif' => [
        //         'id' => $tahunAjaranAktif->id,
        //         'deskripsi' => $tahunAjaranAktif->tahun_ajaran . ' - ' . $tahunAjaranAktif->semester,
        //     ],
        //     'perhitungan_sks' => [
        //         'sks_ekuivalen_jabatan_struktural' => $konversi_sks_jabatan,
        //         'batas_maksimal_sks_mengajar' => $maxAjarSks,
        //         'total_sks_mengajar_saat_ini' => $totalSksMengajar,
        //         'sisa_sks_mengajar_yang_tersedia' => $sisaSksMengajar,
        //     ]
        // ];

        // Opsi 2
        // Langkah Tambahan: Hitung rincian SKS mengajar per program studi
        // $plottingans = $dosen->plottinganPengajarans()
        //     ->whereHas('mappingKelasMatakuliah', function ($query) use ($tahunAjaranAktif) {
        //         $query->where('id_tahun_ajaran', $tahunAjaranAktif->id);
        //     })
        //     ->with('mappingKelasMatakuliah.programStudi:id,nama') // Eager load relasi Program Studi
        //     ->get();

        // $rincianSksPerProdi = $plottingans->groupBy('mappingKelasMatakuliah.programStudi.nama')
        //     ->map(function ($items) {
        //         return $items->sum('beban_sks');
        //     });

        // Langkah 4: Siapkan data untuk respons
        // $responseData = [
        //     'id_dosen' => $dosen->id,
        //     'nama_dosen' => $dosen->name,
        //     'info_tahun_ajaran_aktif' => [
        //         'id' => $tahunAjaranAktif->id,
        //         'deskripsi' => $tahunAjaranAktif->tahun_ajaran . ' - ' . $tahunAjaranAktif->semester,
        //     ],
        //     'perhitungan_sks' => [
        //         'sks_ekuivalen_jabatan' => $konversi_sks_jabatan,
        //         'batas_maksimal_sks_mengajar' => $maxAjarSks,
        //         'total_sks_mengajar_saat_ini' => $totalSksMengajar,
        //         'sisa_sks_mengajar_yang_tersedia' => $sisaSksMengajar,
        //         'rincian_sks_per_prodi' => $rincianSksPerProdi->isNotEmpty() ? $rincianSksPerProdi : null, // Menambahkan detail per prodi
        //     ]
        // ];

        // Opsi 3
        // Langkah Tambahan: Hitung rincian SKS mengajar per program studi
        $programStudis = ProgramStudi::select(['id', 'nama'])->get();
        $rincianSksPerProdi = [];

        foreach ($programStudis as $prodi) {
            $sksDiProdiIni = $dosen->plottinganPengajarans()
                ->whereHas('mappingKelasMatakuliah', function ($queryMKM) use ($tahunAjaranAktif, $prodi) {
                    $queryMKM->where('id_tahun_ajaran', $tahunAjaranAktif->id)
                        ->where('id_program_studi', $prodi->id);
                })
                ->sum('beban_sks');

            // Tambahkan semua prodi ke array, dengan SKS 0 jika tidak mengajar
            $rincianSksPerProdi[$prodi->nama] = (int)$sksDiProdiIni;
        }
        // Langkah 4: Siapkan data untuk respons
        $responseData = [
            'id_dosen' => $dosen->id,
            'nama_dosen' => $dosen->name,
            'info_tahun_ajaran_aktif' => [
                'id' => $tahunAjaranAktif->id,
                'deskripsi' => $tahunAjaranAktif->tahun_ajaran . ' - ' . $tahunAjaranAktif->semester,
            ],
            'perhitungan_sks' => [
                'sks_ekuivalen_jabatan' => $konversi_sks_jabatan,
                'batas_maksimal_sks_mengajar' => $maxAjarSks,
                'total_sks_mengajar_saat_ini' => $totalSksMengajar,
                'sisa_sks_mengajar_yang_tersedia' => $sisaSksMengajar,
                'rincian_sks_per_prodi' => !empty($rincianSksPerProdi) ? $rincianSksPerProdi : null, // Menambahkan detail per prodi
            ]
        ];

        // Langkah 5: Kembalikan respons JSON
        return response()->json([
            'success' => true,
            'message' => 'Rincian beban SKS dosen berhasil dimuat.',
            'data' => $responseData
        ]);
    }
    /**
     * Display the specified resource.
     */
    public function show(Dosen $dosen)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Dosen $dosen)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Dosen $dosen)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Dosen $dosen)
    {
        //
    }
}
