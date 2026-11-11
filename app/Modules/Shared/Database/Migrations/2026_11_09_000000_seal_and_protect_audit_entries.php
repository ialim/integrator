<?php

use App\Modules\Shared\Audit\AuditProtection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Story 1.6: the audit trail nobody can edit.
 *
 * - `audit_seals`, the hash chain: one insert-only row per sealed entry,
 *   holding the entry's sequence, its hash and the previous seal's hash. Seals
 *   never change the entry they seal.
 * - The triggers that reject UPDATE, DELETE and TRUNCATE on both tables
 *   (AuditProtection, from one SQL file the restore script runs too).
 * - `acting_as` names a role ("finance"), not a person, so it becomes a string.
 * - Single-column indexes for "Look up", which matches a subject, an actor
 *   or the person acted for.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE audit_entries ALTER COLUMN acting_as TYPE varchar(32) USING acting_as::text');

        Schema::table('audit_entries', function (Blueprint $table) {
            $table->index('subject_id');
            $table->index('actor_id');
            $table->index('acting_for');
        });

        Schema::create('audit_seals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('sequence')->unique();
            $table->char('hash', 64)->unique();
            $table->char('previous_hash', 64)->unique();
            $table->timestampTz('sealed_at');
        });

        DB::unprepared(AuditProtection::sql());
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS audit_seals_insert_only ON audit_seals;
            DROP TRIGGER IF EXISTS audit_entries_insert_only ON audit_entries;
            DROP FUNCTION IF EXISTS audit_reject_change();
            SQL);

        Schema::dropIfExists('audit_seals');

        Schema::table('audit_entries', function (Blueprint $table) {
            $table->dropIndex(['subject_id']);
            $table->dropIndex(['actor_id']);
            $table->dropIndex(['acting_for']);
        });

        DB::statement("ALTER TABLE audit_entries ALTER COLUMN acting_as TYPE uuid USING CASE WHEN acting_as ~* '^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$' THEN acting_as::uuid END");
    }
};
