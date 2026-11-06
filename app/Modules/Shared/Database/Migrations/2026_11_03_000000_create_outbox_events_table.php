<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * The transactional outbox (AD-4). Actions insert events in their own
 * transaction; the relay hands them to listeners afterwards and marks them
 * relayed. `position` orders the relay; `sequence` numbers each aggregate's
 * events from 1, unique per aggregate. `claimed_until` is a relay's short
 * lease on an event while its listeners run outside any transaction.
 * Payloads hold identifiers and facts, never personal values in clear.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outbox_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type', 100);
            $table->string('aggregate_type', 100);
            $table->uuid('aggregate_id');
            $table->unsignedInteger('sequence');
            $table->jsonb('payload');
            $table->timestampTz('occurred_at');
            $table->timestampTz('available_at');
            $table->timestampTz('claimed_until')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->string('last_error', 700)->nullable();
            $table->timestampTz('relayed_at')->nullable();
            $table->timestampTz('given_up_at')->nullable();

            $table->unique(['aggregate_type', 'aggregate_id', 'sequence']);
        });

        DB::statement('ALTER TABLE outbox_events ADD COLUMN position bigserial NOT NULL UNIQUE');
        DB::statement('CREATE INDEX outbox_events_pending_index ON outbox_events (position) WHERE relayed_at IS NULL AND given_up_at IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('outbox_events');
    }
};
