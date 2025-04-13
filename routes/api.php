<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});
Route::get('/test', function () {
    $data = [
        'test' => 'test'
    ];
    return $data;
});
