<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Note;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * NoteController
 *
 * Handles all CRUD operations for Notes via the RESTful API.
 * Each method returns a clean JSON response with a consistent structure:
 *
 *   Success: { success: true,  data: {...},       message: "..." }
 *   Error:   { success: false, errors: {...},      message: "..." }
 *
 * Auth placeholder: In production, inject the authenticated user via
 * $request->user() and filter notes by user_id accordingly.
 */
class NoteController extends Controller
{
    // ─────────────────────────────────────────────
    // GET /api/notes
    // ─────────────────────────────────────────────

    /**
     * List all notes.
     *
     * Optionally filter by ?user_id=1 (auth placeholder).
     * Returns notes ordered by most recently updated first.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Note::query()->latest('updated_at');

        // Auth placeholder: filter by user_id if provided
        if ($request->has('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        $notes = $query->get()->map(function (Note $note) {
            return $this->formatNote($note);
        });

        return response()->json([
            'success' => true,
            'data'    => $notes,
            'message' => 'Notes retrieved successfully.',
        ]);
    }

    // ─────────────────────────────────────────────
    // GET /api/notes/{id}
    // ─────────────────────────────────────────────

    /**
     * Get a single note by its ID.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $note = Note::find($id);

        if (! $note) {
            return response()->json([
                'success' => false,
                'message' => "Note with ID {$id} not found.",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $this->formatNote($note),
            'message' => 'Note retrieved successfully.',
        ]);
    }

    // ─────────────────────────────────────────────
    // POST /api/notes
    // ─────────────────────────────────────────────

    /**
     * Create a new note.
     *
     * Expected request body (JSON):
     * {
     *   "title":       "My Note Title",       // optional, defaults to 'Untitled Note'
     *   "content":     "# Markdown content",  // optional
     *   "deadline_at": "2026-07-05",          // optional, date format Y-m-d
     *   "user_id":     1                      // optional, auth placeholder
     * }
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title'       => 'nullable|string|max:255',
            'content'     => 'nullable|string',
            'deadline_at' => 'nullable|date_format:Y-m-d',
            'user_id'     => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
                'message' => 'Validation failed.',
            ], 422);
        }

        $note = Note::create([
            'user_id'     => $request->input('user_id'),
            'title'       => $request->input('title') ?: 'Untitled Note', // ?: handles null & empty string
            'content'     => $request->input('content') ?? '',
            'deadline_at' => $request->input('deadline_at'),
        ]);

        return response()->json([
            'success' => true,
            'data'    => $this->formatNote($note),
            'message' => 'Note created successfully.',
        ], 201);
    }

    // ─────────────────────────────────────────────
    // PUT /api/notes/{id}
    // ─────────────────────────────────────────────

    /**
     * Update an existing note (supports partial updates).
     *
     * Only the provided fields will be updated.
     *
     * @param  Request  $request
     * @param  int      $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $note = Note::find($id);

        if (! $note) {
            return response()->json([
                'success' => false,
                'message' => "Note with ID {$id} not found.",
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title'       => 'sometimes|nullable|string|max:255',
            'content'     => 'sometimes|nullable|string',
            'deadline_at' => 'sometimes|nullable|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
                'message' => 'Validation failed.',
            ], 422);
        }

        // Only update fields that were actually sent in the request
        $note->fill($request->only(['title', 'content', 'deadline_at']));
        $note->save();

        return response()->json([
            'success' => true,
            'data'    => $this->formatNote($note),
            'message' => 'Note updated successfully.',
        ]);
    }

    // ─────────────────────────────────────────────
    // DELETE /api/notes/{id}
    // ─────────────────────────────────────────────

    /**
     * Delete a note permanently.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $note = Note::find($id);

        if (! $note) {
            return response()->json([
                'success' => false,
                'message' => "Note with ID {$id} not found.",
            ], 404);
        }

        $note->delete();

        return response()->json([
            'success' => true,
            'data'    => null,
            'message' => "Note '{$note->title}' deleted successfully.",
        ]);
    }

    // ─────────────────────────────────────────────
    // PUT /api/notes/{id}/pomodoro
    // ─────────────────────────────────────────────

    /**
     * Increment the total_pomodoros count for a note.
     *
     * Called by the Vue frontend when:
     *   a) The Pomodoro timer reaches 00:00 naturally, OR
     *   b) The user clicks the "Mock Complete" button for testing.
     *
     * Returns the updated note including the new total_pomodoros value.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function pomodoro(int $id): JsonResponse
    {
        $note = Note::find($id);

        if (! $note) {
            return response()->json([
                'success' => false,
                'message' => "Note with ID {$id} not found.",
            ], 404);
        }

        // Atomically increment using Laravel's increment() to avoid race conditions
        $note->increment('total_pomodoros');
        $note->refresh(); // reload to get updated value

        return response()->json([
            'success' => true,
            'data'    => $this->formatNote($note),
            'message' => "Pomodoro session logged! Total: {$note->total_pomodoros}.",
        ]);
    }

    // ─────────────────────────────────────────────
    // PRIVATE HELPERS
    // ─────────────────────────────────────────────

    /**
     * Format a Note model into a consistent API response array.
     * This is what the Vue frontend will receive and work with.
     *
     * @param  Note  $note
     * @return array
     */
    private function formatNote(Note $note): array
    {
        return [
            'id'               => $note->id,
            'user_id'          => $note->user_id,
            'title'            => $note->title,
            'content'          => $note->content,
            'snippet'          => $note->snippet,       // from getSnippetAttribute()
            'deadline_at'      => $note->deadline_at
                                    ? $note->deadline_at->format('Y-m-d')
                                    : null,
            'total_pomodoros'  => $note->total_pomodoros,
            'created_at'       => $note->created_at?->toISOString(),
            'updated_at'       => $note->updated_at?->toISOString(),
        ];
    }
}
