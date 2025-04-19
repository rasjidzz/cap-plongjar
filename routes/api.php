<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DosenController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\MatakuliahController;
use App\Http\Controllers\RoleController;
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

        Route::apiResource('dosens', DosenController::class);
        Route::apiResource('matakuliahs', MatakuliahController::class);
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
