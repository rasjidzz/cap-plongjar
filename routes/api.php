<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DosenController;
use App\Http\Controllers\JabatanStrukturalController;
use App\Http\Controllers\KelompokKeahlianController;
use App\Http\Controllers\MappingKelasMatakuliahController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\MatakuliahController;
use App\Http\Controllers\ProgramStudiController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TahunAjaranController;
use App\Models\JabatanStruktural;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Authentification and Authorization Stuff
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

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);
});
// Authentification and Authorization Stuff

Route::prefix('roles')->group(function () {
    Route::middleware(['auth:sanctum', 'role:Superadmin'])->group(function () {
        Route::get('/', [RoleController::class, 'getAllRoles']);
        Route::get('/getAllUser', [RoleController::class, 'getAllUser']);
        Route::get('/getAllAssignedUserRoles', [RoleController::class, 'getAllAssignedUserRole']);
        Route::get('/getAllUnassignedUser', [RoleController::class, 'getAllUnassignedUser']);
        Route::get('/getAllUserByRole/{id_role}', [RoleController::class, 'getAllUserByRole']);
        Route::post('/assignRole', [RoleController::class, 'assignRole']);
        Route::post('/revokeRole', [RoleController::class, 'revokeRole']);
    });
});

Route::prefix('masterdata')->group(function () {
    Route::middleware(['auth:sanctum', 'role:Superadmin,ProgramStudi,KelompokKeahlian'])->group(function () {
        Route::post('/addPicData', [MasterDataController::class, 'AddPic']);
        Route::post('/addDosenData', [MasterDataController::class, 'AddDosenData']);
        Route::get('/getAllPic', [MasterDataController::class, 'getAllPic']);
        Route::get('/getAllDosen', [DosenController::class, 'getAllDosen']);
        Route::get('/getAllDosen/{id_kk}', [DosenController::class, 'getAllDosenByKKId']);
        Route::apiResource('dosens', DosenController::class);
        Route::get('/getAllMatakuliah', [MatakuliahController::class, 'index']);
        Route::get('/getDosenDetail/{id_dosen}', [DosenController::class, 'getDosenDetailData']);
        Route::get('/programstudi', [ProgramStudiController::class, 'index']);
        Route::get('/kelompokkeahlian', [KelompokKeahlianController::class, 'index']);
        Route::get('/getmappingkelasmatkulbyidmatkul/{id_matakuliah}', [MappingKelasMatakuliahController::class, 'getMappingKelasMatkulbyIdMatkul']);
    });
    Route::middleware(['auth:sanctum', 'role:Superadmin,ProgramStudi'])->group(function () {
        Route::apiResource('matakuliahs', MatakuliahController::class);
        Route::apiResource('tahunajarans', TahunAjaranController::class);
        Route::apiResource('mappingkelasmatakuliahs', MappingKelasMatakuliahController::class);
    });
    Route::middleware(['auth:sanctum', 'role:Superadmin'])->group(function () {
        Route::apiResource('jabatanstruktural', JabatanStrukturalController::class);
        Route::post('/assignjabatantodosen', [DosenController::class, 'assignJabatanStruktural']);
        Route::post('/revokejabatandosen', [DosenController::class, 'revokeJabatanStrukturalDosen']);
    });
});

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    $data = [
        'user data' => $request->user(),
        'role ' => $request->user()->roles[0]->name,
    ];
    return $data;
});

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});



Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'API endpoint not found.'
    ], 404);
});
