<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Auth Stuff
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

Route::prefix('roles')->group(function () {
    Route::middleware(['auth:sanctum', 'role:Superadmin'])->group(function () {
        Route::get('/', [RoleController::class, 'getAllRoles']);
        Route::get('/getAllUser', [RoleController::class, 'getAllUser']);
        Route::get('/getAllAssignedUserRoles', [RoleController::class, 'getAllAssignedUserRole']);
        Route::post('/assignRole', [RoleController::class, 'assignRole']);
        Route::post('/revokeRole', [RoleController::class, 'revokeRole']);
    });
});

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});
