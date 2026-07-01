<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Note Model
 *
 * Represents a developer note in the Dev Productivity Hub.
 * Each note belongs to a user and tracks Markdown content,
 * an optional deadline, and total Pomodoro sessions completed.
 *
 * @property int         $id
 * @property int|null    $user_id
 * @property string      $title
 * @property string|null $content
 * @property string|null $deadline_at   Format: Y-m-d
 * @property int         $total_pomodoros
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Note extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'notes';

    /**
     * Mass-assignable attributes.
     * These are the only fields that can be set via create() or fill().
     */
    protected $fillable = [
        'user_id',
        'title',
        'content',
        'deadline_at',
        'total_pomodoros',
    ];

    /**
     * Attribute casting.
     * Ensures proper PHP types when accessing model properties.
     */
    protected $casts = [
        'deadline_at'     => 'date:Y-m-d',  // Cast to Carbon, formatted as Y-m-d
        'total_pomodoros' => 'integer',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
    ];

    /**
     * Default attribute values.
     */
    protected $attributes = [
        'total_pomodoros' => 0,
        'title'           => 'Untitled Note',
    ];

    // ─────────────────────────────────────────────
    // RELATIONSHIPS
    // ─────────────────────────────────────────────

    /**
     * A note belongs to a User.
     * Uncomment when the auth system is implemented.
     */
    // public function user(): BelongsTo
    // {
    //     return $this->belongsTo(User::class);
    // }

    // ─────────────────────────────────────────────
    // SCOPES
    // ─────────────────────────────────────────────

    /**
     * Scope: filter notes by a specific user ID.
     *
     * Usage: Note::forUser(1)->get();
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: get notes that have an upcoming deadline.
     *
     * Usage: Note::withUpcomingDeadline()->get();
     */
    public function scopeWithUpcomingDeadline($query)
    {
        return $query->whereNotNull('deadline_at')
                     ->where('deadline_at', '>=', now()->startOfDay());
    }

    // ─────────────────────────────────────────────
    // ACCESSORS
    // ─────────────────────────────────────────────

    /**
     * Return a short plain-text snippet of the content (for API responses).
     */
    public function getSnippetAttribute(): string
    {
        // Strip Markdown syntax and return first 120 characters
        $plain = preg_replace('/```[\s\S]*?```/', '', $this->content ?? '');
        $plain = preg_replace('/[#*`>\-]/', '', $plain);
        return mb_substr(trim($plain), 0, 120);
    }
}
