<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * The audit trail, insert-only: only the Shared audit writer touches it, and
 * only to insert. Rows hold identifiers and field differences, never personal
 * values. `sequence` orders rows for the hash chain Story 1.6 adds, together
 * with the sealing job and the trigger that rejects UPDATE and DELETE.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->timestampTz('occurred_at');
            $table->string('actor_type', 16);
            $table->uuid('actor_id')->nullable();
            $table->uuid('acting_as')->nullable();
            $table->uuid('acting_for')->nullable();
            $table->string('action', 100);
            $table->string('subject_type', 100);
            $table->uuid('subject_id')->nullable();
            $table->jsonb('changes');
            $table->string('request_id', 64)->nullable();

            $table->index(['subject_type', 'subject_id']);
            $table->index(['actor_type', 'actor_id']);
        });

        DB::statement('ALTER TABLE audit_entries ADD COLUMN sequence bigserial NOT NULL UNIQUE');
        DB::statement("ALTER TABLE audit_entries ADD CONSTRAINT audit_entries_actor_check CHECK (actor_type IN ('system', 'staff', 'user') AND (actor_type = 'system') = (actor_id IS NULL))");
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_entries');
    }
};
