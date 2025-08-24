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
        Route::post('/register', [AuthController::class, 'register']); // -> Done
        Route::post('/login', [AuthController::class, 'login']); // -> Done

        Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']); // -> Done
    });
    // 1. Authentification and Authorization Stuff

    // 2. Role Management Stuff
    Route::prefix('roles')->group(function () {
        Route::middleware(['auth:sanctum', 'role:Superadmin'])->group(function () {
            Route::get('/', [RoleController::class, 'getAllRoles']); // -> Done

            // NEW UPDATED
            Route::get('/assigned-users', [RoleController::class, 'getAllAssignedUserRole']); // -> Done
            Route::get('/unassigned-users', [RoleController::class, 'getAllUnassignedUser']); // -> Done
            Route::get('/{id_role}/users', [RoleController::class, 'getAllUserByRole']); // -> Done
            Route::post('/{id_role}/users', [RoleController::class, 'assignRoleV2']); // -> Done
            Route::delete('/{id_role}/users/{id_user}/', [RoleController::class, 'destroy']); // -> Done
            Route::post('/assign-scoped-role', [RoleController::class, 'assignScopedRole']); // -> Done

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
            Route::get('/pics', [MasterDataController::class, 'getAllPic']); // -> Done

            // DOSEN MANAGEMENT
            Route::post('/addDosenData', [MasterDataController::class, 'AddDosenData']);  // -> NOT USED
            Route::get('/getAllDosen', [DosenController::class, 'getAllDosen']);  // -> Done
            Route::get('/getAllDosen/{id_kk}', [DosenController::class, 'getAllDosenByKKId']);  // NOT USED
            Route::get('/dosens/by-kelompok-keahlian/{id_kk}', [DosenController::class, 'getAllDosenByKKId']);  // -> Done
            Route::get('/dosens/tanpa-jabatan-struktural', [DosenController::class, 'getAllDosenTanpaJabatanStruktural']);  // -> Done
            Route::get('/dosens/dengan-jabatan-struktural', [DosenController::class, 'getDosenDenganJabatanStruktural']);  // -> Done
            Route::get('/dosens/by-jabatan-struktural/{id_jabatan_struktural}', [DosenController::class, 'getAllDosenByJabatanStrukturalId']);  // -> Done
            Route::apiResource('dosens', DosenController::class);  // -> Done
            Route::get('/getDosenDetail/{id_dosen}', [DosenController::class, 'getDosenDetailData']);  // -> Done

            // MATAKULIAH, PROGRAM_STUDI, KELOMPOK_KEAHLIAN, MAPPING_KELAS_MATKUL
            Route::get('/getAllMatakuliah', [MatakuliahController::class, 'index']);  // -> NOT USED
            Route::get('/kelompokkeahlian', [KelompokKeahlianController::class, 'index']);  // -> Done
            Route::get('/getmappingkelasmatkulbyidmatkul/{id_matakuliah}', [MappingKelasMatakuliahController::class, 'getMappingKelasMatkulbyIdMatkul']); // -> NOT USED
            Route::get('/mapping-kelas-matakuliah/matakuliah/{id_matakuliah}/tahun-ajaran/{id_tahun_ajaran}/program-studi/{id_program_studi}', [MappingKelasMatakuliahController::class, 'getMappingKelasMatakuliahByIdMatakuliahIdTahunAjaranandIdProgramStudi']);  // -> Done
            Route::get('/mapping-kelas-matakuliah/by-matakuliah/{id_matakuliah}/tahun-ajaran/{id_tahunajaran}', [MappingKelasMatakuliahController::class, 'getMappingKelasMatkulByIdMatkulandIdTahunAjaran']); // -> Done
            Route::get('/mapping-kelas-matakuliah/by-matakuliah/{id_matakuliah}/tahun-ajaran/{id_tahun_ajaran}/logged-in-prodi', [MappingKelasMatakuliahController::class, 'getMappingByMatkulTahunAjaranAndAuthProdi']); // -> Done
            Route::get('/mapping-kelas-matakuliah/by-matakuliah/{id_matakuliah}/tahun-ajaran/{id_tahun_ajaran}/logged-in-kk', [MappingKelasMatakuliahController::class, 'getMappingByMatkulTahunAjaranAndAuthKK']); // -> NOT USED

            // KOORDINATOR_MATAKULIAH (SUPER_ADMIN, PROGRAM_STUDI)
            Route::post('/koordinator-matakuliah/revoke-by-program-studi', [KoordinatorMatakuliahController::class, 'revokeKoordinatorByProgramStudi']); // -> NOT USED
            Route::apiResource('koordinator-matakuliah', KoordinatorMatakuliahController::class); // -> Done
            Route::post('/koordinator-matakuliah/assign-by-program-studi', [KoordinatorMatakuliahController::class, 'assignKoordinatorByProgramStudi']); // -> Done
            Route::post('/koordinator-matakuliah/assign-by-program-studi/by-auth-prodi', [KoordinatorMatakuliahController::class, 'assignKoordinatorByProgramStudiWithLoggedInProdi']); // -> DONE
        });

        // ROLE PROGRAM STUDI ONLY (SUPER_ADMIN AND PROGRAM_STUDI)
        Route::middleware(['auth:sanctum', 'role:Superadmin,ProgramStudi,KelompokKeahlian'])->group(function () {
            // Route untuk mengambil mata kuliah berdasarkan Program Studi dari user yang sedang login
            Route::get('/matakuliahs/by-auth-prodi', [MatakuliahController::class, 'getMatakuliahByPicProgramStudi']); // -> DONE
            Route::get('/matakuliahs/by-auth-kk', [MatakuliahController::class, 'getMatakuliahByPicKelompokKeahlian']); // -> Done
            Route::get('/matakuliahs/by-auth-prodi-and-all-kk', [MatakuliahController::class, 'getMatakuliahForPlottingByProdiAndKK']); // -> DONE
            Route::apiResource('matakuliahs', MatakuliahController::class); // -> DONE
            // Route::get('/getMatakuliahProdi', [MatakuliahController::class, 'getMatakuliahByPicProgramStudi']);
            // Route::apiResource('tahunajarans', TahunAjaranController::class);
            Route::apiResource('mappingkelasmatakuliahs', MappingKelasMatakuliahController::class); // -> DONE
        });

        Route::middleware(['auth:sanctum', 'role:Superadmin'])->group(function () {
            Route::prefix('dosen')->group(function () {
                Route::post('/{id_dosen}/jabatan-struktural', [DosenController::class, 'assignJabatanStrukturalV2']); // -> done
                Route::delete('/{id_dosen}/jabatan-struktural', [DosenController::class, 'revokeJabatanStrukturalDosenV2']); // -> DONE
            });
            // JABATAN STRUKTURAL MANAGEMENT (SUPER_ADMIN ONLY)
            Route::apiResource('jabatanstruktural', JabatanStrukturalController::class); // -> DONE
            // NO LONGER USED
            Route::post('/assignjabatantodosen', [DosenController::class, 'assignJabatanStruktural']); // => INI DIUBAH PRINSIP REST
            Route::post('/revokejabatandosen', [DosenController::class, 'revokeJabatanStrukturalDosen']); // => INI DIUBAH PRINSIP REST
            // NO LONGER USED
            Route::apiResource('/program-studi', ProgramStudiController::class);  // -> DONE

            // Tahun Ajaran Management (SUPER_ADMIN ONLY
            Route::apiResource('tahunajarans', TahunAjaranController::class);  // -> DONE
            Route::post('/tahun-ajaran/{id_tahun_ajaran}/set-active', [TahunAjaranController::class, 'setActiveTahunAjaran']);  // -> DONE

            // ASSIGN KETUA KELOMPOK KEAHLIAN
            Route::post('/kelompok-keahlian/{id_kk}/assign-ketua', [KelompokKeahlianController::class, 'assignKetua']); // -> NOT USED
            Route::post('/kelompok-keahlian/{id_kk}/revoke-ketua', [KelompokKeahlianController::class, 'revokeKetua']);  // -> NOT USED
        });

        // MAPPING KELAS MATAKULIAH MANAGEMENT (SUPER_ADMIN, PROGRAM_STUDI)
        // Route::apiResource('mappingkelasmatakuliahs', MappingKelasMatakuliahController::class)->middleware('role:Superadmin,ProgramStudi');
        // Specific read operation accessible by Superadmin, ProgramStudi, KelompokKeahlian
        Route::get('/mappingkelasmatakuliahs/by-matakuliah/{id_matakuliah}', [MappingKelasMatakuliahController::class, 'getMappingKelasMatkulbyIdMatkul'])
            ->middleware('role:Superadmin,ProgramStudi,KelompokKeahlian')
            ->name('mappingkelasmatakuliahs.byMatakuliah'); // NOT USED

        // GET Active Tahun Ajaran
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/tahun-ajaran/aktif', [TahunAjaranController::class, 'getActiveTahunAjaran'])->name('tahunAjaran.getActive'); // DONE
            Route::get('/get-all-tahun-ajaran', [TahunAjaranController::class, 'index']); // NOT USED
            Route::get('/programstudi', [ProgramStudiController::class, 'index']); // NOT USED
        });
    });
    // 3. Master Data

    Route::prefix('plottingan-pengajaran')->group(function () {
        Route::middleware(['auth:sanctum', 'role:Superadmin,ProgramStudi,KelompokKeahlian'])->group(function () {
            Route::apiResource('start-plottingan-pengajaran', PlottinganPengajaranController::class); // -> DONE
            Route::delete('/unassign/{plottinganPengajaran}', [PlottinganPengajaranController::class, 'unassignPlottingan']);  // -> DONE
            Route::get('/get-hasil-plottingan-pengajaran/{id_tahun_ajaran}', [PlottinganPengajaranController::class, 'getHasilPlottinganPengajaranByTahunAjaranId']);  // -> NOT USED
            Route::get('/dosen/laporan-beban-sks/tahun-ajaran/{id_tahun_ajaran}', [DosenController::class, 'getLaporanBebanSksDosen']);  // -> DONE
            Route::get('/dosen/{id_dosen}/riwayat-pengajaran', [DosenController::class, 'getRiwayatPengajaran']);  // -> DONE
            Route::get('/dosen/{id_dosen}/beban-sks-aktif', [DosenController::class, 'getBebanSksDosenByIdDosenandActiveTahunAjaran']); // -> NOT USES
            Route::get('/dosen/{id_dosen}/tahun-ajaran/{id_tahun_ajaran}', [DosenController::class, 'getBebanSksDosenByIdDosenandIdTahunAjaran']);  // -> DONE
        });
        Route::middleware(['auth:sanctum', 'role:Superadmin,LayananAkademik,KepalaUrusanLab,ProgramStudi,KelompokKeahlian'])->group(function () {
            Route::get('/tahun-ajaran/{id_tahun_ajaran}/program-studi/{id_program_studi}', [PlottinganPengajaranController::class, 'getHasilPlottinganByProdiDanTahunAjaran']); // -> DONE
            Route::get('/summary', [PlottinganPengajaranController::class, 'getPlottinganSummary']);  // -> DONE
            Route::get('/export/tahun-ajaran/{id_tahun_ajaran}', [PlottinganPengajaranController::class, 'exportHasilPlottinganToExcel']); // -> DONE
            Route::get('/export/tahun-ajaran/{id_tahun_ajaran}/program-studi/{id_program_studi}', [PlottinganPengajaranController::class, 'exportHasilPlottinganByProdiDanTahunAjaranToExcel']);  // -> DONE
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
