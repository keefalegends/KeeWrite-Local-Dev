<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\NoteController;

Route::prefix('notes')->group(function () {
    Route::get('/',           [NoteController::class, 'index']);
    Route::post('/',          [NoteController::class, 'store']);
    Route::get('/{id}',       [NoteController::class, 'show']);
    Route::put('/{id}',       [NoteController::class, 'update']);
    Route::delete('/{id}',    [NoteController::class, 'destroy']);
    Route::put('/{id}/pomodoro', [NoteController::class, 'pomodoro']);
});
