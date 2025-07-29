<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DosenController;
use App\Http\Controllers\JabatanStrukturalController;
use App\Http\Controllers\KelompokKeahlianController;
use App\Http\Controllers\KoordinatorMatakuliahController;
use App\Http\Controllers\MappingKelasMatakuliahController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\MatakuliahController;
use App\Http\Controllers\PlottinganPengajaranController;
use App\Http\Controllers\ProgramStudiController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TahunAjaranController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// 1. Authentification and Authorization Stuff
/*
    role :
        1. Superadmin
        2. ProgramStudi
        3. KelompokKeahlian
        4. LayananAkademik
        5. KepalaUrusanLab

    Kelompok Keahlian :
        1. SEAL
        2. CITI
        3. DSIS

    Program Studi :
        1. S1 Informatika
        2. S1 Rekayasa Perangkat Lunak
        3. S1 Data Sains
        4. S1 Information Technology
*/

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);

        Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);
    });
    // 1. Authentification and Authorization Stuff

    // 2. Role Management Stuff
    Route::prefix('roles')->group(function () {
        Route::middleware(['auth:sanctum', 'role:Superadmin'])->group(function () {
            Route::get('/', [RoleController::class, 'getAllRoles']);

            // NEW UPDATED
            Route::get('/assigned-users', [RoleController::class, 'getAllAssignedUserRole']);
            Route::get('/unassigned-users', [RoleController::class, 'getAllUnassignedUser']);
            Route::get('/{id_role}/users', [RoleController::class, 'getAllUserByRole']);
            Route::post('/{id_role}/users', [RoleController::class, 'assignRoleV2']);
            Route::delete('/{id_role}/users/{id_user}/', [RoleController::class, 'destroy']);
            Route::post('/assign-scoped-role', [RoleController::class, 'assignScopedRole']);

            // => NO LONGER USED
            Route::get('/getAllUserByRole/{id_role}', [RoleController::class, 'getAllUserByRole']); // => NO LONGER USED
            Route::get('/getAllUser', [RoleController::class, 'getAllUser']); // => NO LONGER USED -> /USERS
            Route::get('/getAllAssignedUserRoles', [RoleController::class, 'getAllAssignedUserRole']); // => NO LONGER USED
            Route::post('/assignRole', [RoleController::class, 'assignRole']); // => NOT FULLY RESTFUL
            Route::post('/revokeRole', [RoleController::class, 'revokeRole']);  // => NOT FULLY RESTFUL
            Route::get('/getAllUnassignedUser', [RoleController::class, 'getAllUnassignedUser']); // => NO LONGER USED
        });
    });
    // 2. Role Management Stuff

    // 3. Master Data
    Route::prefix('masterdata')->group(function () {
        // SUPER_ADMIN, PROGRAM_STUDI, KELOMPOK_KEAHLIAN
        Route::middleware(['auth:sanctum', 'role:Superadmin,ProgramStudi,KelompokKeahlian'])->group(function () {
            // PIC MANAGEMENT
            // Route::post('/addPicData', [MasterDataController::class, 'AddPic']); => NO LONGER USED
            Route::get('/getAllPic', [MasterDataController::class, 'getAllPic']); // => NO LONGER USED
            Route::get('/pics', [MasterDataController::class, 'getAllPic']);

            // DOSEN MANAGEMENT
            Route::post('/addDosenData', [MasterDataController::class, 'AddDosenData']);
            Route::get('/getAllDosen', [DosenController::class, 'getAllDosen']);
            Route::get('/getAllDosen/{id_kk}', [DosenController::class, 'getAllDosenByKKId']);
            Route::get('/dosens/by-kelompok-keahlian/{id_kk}', [DosenController::class, 'getAllDosenByKKId']);
            Route::get('/dosens/tanpa-jabatan-struktural', [DosenController::class, 'getAllDosenTanpaJabatanStruktural']);
            Route::get('/dosens/dengan-jabatan-struktural', [DosenController::class, 'getDosenDenganJabatanStruktural']);
            Route::get('/dosens/by-jabatan-struktural/{id_jabatan_struktural}', [DosenController::class, 'getAllDosenByJabatanStrukturalId']);
            Route::apiResource('dosens', DosenController::class);
            Route::get('/getDosenDetail/{id_dosen}', [DosenController::class, 'getDosenDetailData']);

            // MATAKULIAH, PROGRAM_STUDI, KELOMPOK_KEAHLIAN, MAPPING_KELAS_MATKUL
            Route::get('/getAllMatakuliah', [MatakuliahController::class, 'index']);
            Route::get('/kelompokkeahlian', [KelompokKeahlianController::class, 'index']);
            Route::get('/getmappingkelasmatkulbyidmatkul/{id_matakuliah}', [MappingKelasMatakuliahController::class, 'getMappingKelasMatkulbyIdMatkul']);
            Route::get('/mapping-kelas-matakuliah/matakuliah/{id_matakuliah}/tahun-ajaran/{id_tahun_ajaran}/program-studi/{id_program_studi}', [MappingKelasMatakuliahController::class, 'getMappingKelasMatakuliahByIdMatakuliahIdTahunAjaranandIdProgramStudi']);
            Route::get('/mapping-kelas-matakuliah/by-matakuliah/{id_matakuliah}/tahun-ajaran/{id_tahunajaran}', [MappingKelasMatakuliahController::class, 'getMappingKelasMatkulByIdMatkulandIdTahunAjaran']);
            Route::get('/mapping-kelas-matakuliah/by-matakuliah/{id_matakuliah}/tahun-ajaran/{id_tahun_ajaran}/logged-in-prodi', [MappingKelasMatakuliahController::class, 'getMappingByMatkulTahunAjaranAndAuthProdi']);
            Route::get('/mapping-kelas-matakuliah/by-matakuliah/{id_matakuliah}/tahun-ajaran/{id_tahun_ajaran}/logged-in-kk', [MappingKelasMatakuliahController::class, 'getMappingByMatkulTahunAjaranAndAuthKK']);

            // KOORDINATOR_MATAKULIAH (SUPER_ADMIN, PROGRAM_STUDI)
            Route::post('/koordinator-matakuliah/revoke-by-program-studi', [KoordinatorMatakuliahController::class, 'revokeKoordinatorByProgramStudi']);
            Route::apiResource('koordinator-matakuliah', KoordinatorMatakuliahController::class);
            Route::post('/koordinator-matakuliah/assign-by-program-studi', [KoordinatorMatakuliahController::class, 'assignKoordinatorByProgramStudi']);
            Route::post('/koordinator-matakuliah/assign-by-program-studi/by-auth-prodi', [KoordinatorMatakuliahController::class, 'assignKoordinatorByProgramStudiWithLoggedInProdi']);
        });

        // ROLE PROGRAM STUDI ONLY (SUPER_ADMIN AND PROGRAM_STUDI)
        Route::middleware(['auth:sanctum', 'role:Superadmin,ProgramStudi,KelompokKeahlian'])->group(function () {
            // Route untuk mengambil mata kuliah berdasarkan Program Studi dari user yang sedang login
            Route::get('/matakuliahs/by-auth-prodi', [MatakuliahController::class, 'getMatakuliahByPicProgramStudi']);
            Route::get('/matakuliahs/by-auth-kk', [MatakuliahController::class, 'getMatakuliahByPicKelompokKeahlian']);
            Route::get('/matakuliahs/by-auth-prodi-and-all-kk', [MatakuliahController::class, 'getMatakuliahForPlottingByProdiAndKK']);
            Route::apiResource('matakuliahs', MatakuliahController::class);
            // Route::get('/getMatakuliahProdi', [MatakuliahController::class, 'getMatakuliahByPicProgramStudi']);
            Route::apiResource('tahunajarans', TahunAjaranController::class);
            Route::apiResource('mappingkelasmatakuliahs', MappingKelasMatakuliahController::class);
        });

        Route::middleware(['auth:sanctum', 'role:Superadmin'])->group(function () {
            Route::prefix('dosen')->group(function () {
                Route::post('/{id_dosen}/jabatan-struktural', [DosenController::class, 'assignJabatanStrukturalV2']);
                Route::delete('/{id_dosen}/jabatan-struktural', [DosenController::class, 'revokeJabatanStrukturalDosenV2']);
            });
            // JABATAN STRUKTURAL MANAGEMENT (SUPER_ADMIN ONLY)
            Route::apiResource('jabatanstruktural', JabatanStrukturalController::class);
            // NO LONGER USED
            Route::post('/assignjabatantodosen', [DosenController::class, 'assignJabatanStruktural']); // => INI DIUBAH PRINSIP REST
            Route::post('/revokejabatandosen', [DosenController::class, 'revokeJabatanStrukturalDosen']); // => INI DIUBAH PRINSIP REST
            // NO LONGER USED
            Route::apiResource('/program-studi', ProgramStudiController::class);

            // Tahun Ajaran Management (SUPER_ADMIN ONLY)
            Route::post('/tahun-ajaran/{id_tahun_ajaran}/set-active', [TahunAjaranController::class, 'setActiveTahunAjaran']);

            // ASSIGN KETUA KELOMPOK KEAHLIAN
            Route::post('/kelompok-keahlian/{id_kk}/assign-ketua', [KelompokKeahlianController::class, 'assignKetua']);
            Route::post('/kelompok-keahlian/{id_kk}/revoke-ketua', [KelompokKeahlianController::class, 'revokeKetua']);
        });

        // MAPPING KELAS MATAKULIAH MANAGEMENT (SUPER_ADMIN, PROGRAM_STUDI)
        // Route::apiResource('mappingkelasmatakuliahs', MappingKelasMatakuliahController::class)->middleware('role:Superadmin,ProgramStudi');
        // Specific read operation accessible by Superadmin, ProgramStudi, KelompokKeahlian
        Route::get('/mappingkelasmatakuliahs/by-matakuliah/{id_matakuliah}', [MappingKelasMatakuliahController::class, 'getMappingKelasMatkulbyIdMatkul'])
            ->middleware('role:Superadmin,ProgramStudi,KelompokKeahlian')
            ->name('mappingkelasmatakuliahs.byMatakuliah');

        // GET Active Tahun Ajaran
        Route::middleware('auth:sanctum')->group(function () {
            // ... route lain
            Route::get('/tahun-ajaran/aktif', [TahunAjaranController::class, 'getActiveTahunAjaran'])->name('tahunAjaran.getActive');
            Route::get('/get-all-tahun-ajaran', [TahunAjaranController::class, 'index']);
            Route::get('/programstudi', [ProgramStudiController::class, 'index']);
        });
    });
    // 3. Master Data

    Route::prefix('plottingan-pengajaran')->group(function () {
        Route::middleware(['auth:sanctum', 'role:Superadmin,ProgramStudi,KelompokKeahlian'])->group(function () {
            Route::apiResource('start-plottingan-pengajaran', PlottinganPengajaranController::class);
            Route::delete('/unassign/{plottinganPengajaran}', [PlottinganPengajaranController::class, 'unassignPlottingan']);
            Route::get('/get-hasil-plottingan-pengajaran/{id_tahun_ajaran}', [PlottinganPengajaranController::class, 'getHasilPlottinganPengajaranByTahunAjaranId']);
            Route::get('/dosen/laporan-beban-sks/tahun-ajaran/{id_tahun_ajaran}', [DosenController::class, 'getLaporanBebanSksDosen']);
            Route::get('/dosen/{id_dosen}/riwayat-pengajaran', [DosenController::class, 'getRiwayatPengajaran']);
            Route::get('/dosen/{id_dosen}/beban-sks-aktif', [DosenController::class, 'getBebanSksDosenByIdDosenandActiveTahunAjaran']);
            Route::get('/dosen/{id_dosen}/tahun-ajaran/{id_tahun_ajaran}', [DosenController::class, 'getBebanSksDosenByIdDosenandIdTahunAjaran']);
        });
        Route::middleware(['auth:sanctum', 'role:Superadmin,LayananAkademik,KepalaUrusanLab,ProgramStudi,KelompokKeahlian'])->group(function () {
            Route::get('/tahun-ajaran/{id_tahun_ajaran}/program-studi/{id_program_studi}', [PlottinganPengajaranController::class, 'getHasilPlottinganByProdiDanTahunAjaran']);
            Route::get('/summary', [PlottinganPengajaranController::class, 'getPlottinganSummary']);
            Route::get('/export/tahun-ajaran/{id_tahun_ajaran}', [PlottinganPengajaranController::class, 'exportHasilPlottinganToExcel']);
            Route::get('/export/tahun-ajaran/{id_tahun_ajaran}/program-studi/{id_program_studi}', [PlottinganPengajaranController::class, 'exportHasilPlottinganByProdiDanTahunAjaranToExcel']);
        });
    });

    Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
        $data = [
            'user data' => $request->user(),
            'role ' => $request->user()->roles()
        ];
        return $data;
    });
});



Route::get('/testgetdata', [ProgramStudiController::class, 'index']);

Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'API endpoint not found.'
    ], 404);
});
