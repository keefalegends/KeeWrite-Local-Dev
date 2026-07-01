<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_notes_table
 *
 * Creates the `notes` table for the Dev Productivity Hub application.
 * Each note belongs to a user (auth placeholder), has a Markdown content body,
 * an optional deadline, and tracks total Pomodoro focus sessions.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notes', function (Blueprint $table) {
            // Primary key
            $table->id();

            // Foreign key to users table (auth placeholder — can be nullable for prototyping)
            $table->unsignedBigInteger('user_id')->nullable()->index();

            // Note title (required)
            $table->string('title')->default('Untitled Note');

            // Note body in Markdown (stored as longText to support large documents)
            $table->longText('content')->nullable();

            // Optional deadline date (not datetime — date only is intentional)
            $table->date('deadline_at')->nullable();

            // Count of completed Pomodoro focus sessions for this note
            $table->unsignedInteger('total_pomodoros')->default(0);

            // Timestamps (created_at, updated_at)
            $table->timestamps();

            // Optional: add soft deletes for recovery support
            // $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
