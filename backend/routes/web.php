<?php

use Illuminate\Support\Facades\Route;

// Mengarahkan semua rute web (termasuk refresh halaman SPA) ke entry point Vue
Route::fallback(function () {
    return file_get_contents(public_path('index.html'));
});
