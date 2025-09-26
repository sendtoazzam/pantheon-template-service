<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/status', function () {
    return view('status');
});

Route::get('/docs', function () {
    return redirect('/api/documentation');
});
