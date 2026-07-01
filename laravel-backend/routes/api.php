<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\NoteController;

/*
|--------------------------------------------------------------------------
| API Routes — Dev Productivity Hub
|--------------------------------------------------------------------------
|
| All routes here are automatically prefixed with /api
| and assigned the 'api' middleware group.
|
| Auth placeholder: For production, wrap these in `auth:sanctum` middleware.
| Example:
|   Route::middleware('auth:sanctum')->group(function () { ... });
|
*/

// ── Notes CRUD ──────────────────────────────────────────────────────────
//
//  GET    /api/notes              → NoteController@index    (list all notes)
//  POST   /api/notes              → NoteController@store    (create a note)
//  GET    /api/notes/{id}         → NoteController@show     (get single note)
//  PUT    /api/notes/{id}         → NoteController@update   (update a note)
//  DELETE /api/notes/{id}         → NoteController@destroy  (delete a note)
//  PUT    /api/notes/{id}/pomodoro→ NoteController@pomodoro (increment session)
// ────────────────────────────────────────────────────────────────────────

Route::prefix('notes')->group(function () {

    // List all notes (optionally filtered by user_id query param)
    Route::get('/', [NoteController::class, 'index']);

    // Create a new note
    Route::post('/', [NoteController::class, 'store']);

    // Get a single note by ID
    Route::get('/{id}', [NoteController::class, 'show']);

    // Update a note (full or partial update)
    Route::put('/{id}', [NoteController::class, 'update']);

    // Delete a note
    Route::delete('/{id}', [NoteController::class, 'destroy']);

    // Increment total_pomodoros for a note
    // Called when a Pomodoro session completes in the frontend timer
    Route::put('/{id}/pomodoro', [NoteController::class, 'pomodoro']);
});
