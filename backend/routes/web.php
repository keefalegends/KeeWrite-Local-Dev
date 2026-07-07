<?php

use Illuminate\Support\Facades\Route;

// Mengarahkan semua rute web (termasuk refresh halaman SPA) ke entry point Vue di public_html
Route::fallback(function () {
    return file_get_contents(base_path('../public_html/index.html'));
});
