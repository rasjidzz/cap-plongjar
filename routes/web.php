<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // return ['Laravel' => app()->version()];
    return ['error' => 'ini kesini route /'];
});

require __DIR__ . '/auth.php';
