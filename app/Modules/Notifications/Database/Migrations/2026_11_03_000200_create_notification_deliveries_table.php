<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Delivery keys (AD-4): one row per outbox event and channel, committed as
 * `sending` before the provider is called, then `sent`, or `uncertain` when
 * the outcome is unknown (never sent again). A definite failure deletes the
 * row so a retry sends. The unique key means relaying an event twice sends
 * once. No address or message text is kept.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_deliveries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('event_id')->constrained('outbox_events')->restrictOnDelete();
            $table->string('channel', 32);
            $table->string('status', 16);
            $table->string('provider_message_id')->nullable();
            $table->timestampTz('created_at');
            $table->timestampTz('sent_at')->nullable();

            $table->unique(['event_id', 'channel']);
        });

        DB::statement("ALTER TABLE notification_deliveries ADD CONSTRAINT notification_deliveries_status_check CHECK (status IN ('sending', 'sent', 'uncertain'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_deliveries');
    }
};
