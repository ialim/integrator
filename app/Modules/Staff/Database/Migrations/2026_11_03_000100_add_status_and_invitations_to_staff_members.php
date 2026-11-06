<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Staff accounts gain a status from the declared Staff transition table
 * (Invited, Active, Deactivated) in place of the `is_active` flag, and an
 * invitation: the hash of the link's token (never the token), when it
 * expires, when it was accepted and which Admin sent it. An invited member has
 * no password until they accept. Existing accounts (the founder's Admin) keep
 * their state: active stays Active.
 *
 * An email address is unique only among Invited and Active accounts (owner's
 * decision, 6 November 2026): a deactivated person who comes back gets a new
 * account, and the old one stays for the audit trail.
 *
 * `staff_sessions` records each staff session id at sign-in, because the
 * database session handler records only the default guard's user, so a
 * deactivation can delete every stored session of that member.
 */
return new class extends Migration
{
    public const EMAIL_INDEX = 'staff_members_email_current_unique';

    public function up(): void
    {
        Schema::table('staff_members', function (Blueprint $table) {
            $table->string('status', 16)->default('invited');
            $table->string('invitation_token_hash', 64)->nullable()->unique();
            $table->timestampTz('invitation_expires_at')->nullable();
            $table->timestampTz('invitation_accepted_at')->nullable();
            $table->foreignUuid('invited_by')->nullable()->constrained('staff_members')->restrictOnDelete();
            $table->timestampTz('deactivated_at')->nullable();
        });

        DB::statement("UPDATE staff_members SET status = CASE WHEN is_active THEN 'active' ELSE 'deactivated' END");

        Schema::table('staff_members', function (Blueprint $table) {
            $table->dropColumn('is_active');
            $table->dropUnique(['email']);
            $table->string('password')->nullable()->change();
        });

        DB::statement('CREATE UNIQUE INDEX '.self::EMAIL_INDEX." ON staff_members (email) WHERE status IN ('invited', 'active')");
        DB::statement("ALTER TABLE staff_members ADD CONSTRAINT staff_members_status_check CHECK (status IN ('invited', 'active', 'deactivated'))");
        DB::statement("ALTER TABLE staff_members ADD CONSTRAINT staff_members_active_password_check CHECK (status <> 'active' OR password IS NOT NULL)");

        Schema::create('staff_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('staff_member_id')->constrained('staff_members')->restrictOnDelete();
            $table->string('session_id')->unique();
            $table->timestampTz('created_at');
        });
    }

    /*
     * Back to the `is_active` flag: Active, and an invitee who has already set
     * a password, can sign in, so count as active; an unanswered invitation
     * and a deactivated account do not. Two things are restored only when the
     * data allows, because the old schema cannot hold it otherwise:
     * `password NOT NULL` (not while an invitee has no password yet) and the
     * plain unique email index (not while a returning person has a second
     * account under the same address). Then they are left as they are.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_sessions');

        DB::statement('ALTER TABLE staff_members DROP CONSTRAINT staff_members_active_password_check');
        DB::statement('ALTER TABLE staff_members DROP CONSTRAINT staff_members_status_check');
        DB::statement('DROP INDEX '.self::EMAIL_INDEX);

        Schema::table('staff_members', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
        });

        DB::statement("UPDATE staff_members SET is_active = (status = 'active' OR (status = 'invited' AND password IS NOT NULL))");

        Schema::table('staff_members', function (Blueprint $table) {
            $table->dropConstrainedForeignId('invited_by');
            $table->dropColumn(['status', 'invitation_token_hash', 'invitation_expires_at', 'invitation_accepted_at', 'deactivated_at']);
        });

        if (! DB::table('staff_members')->whereNull('password')->exists()) {
            Schema::table('staff_members', fn (Blueprint $table) => $table->string('password')->nullable(false)->change());
        }

        if (! DB::table('staff_members')->select('email')->groupBy('email')->havingRaw('count(*) > 1')->exists()) {
            Schema::table('staff_members', fn (Blueprint $table) => $table->unique('email'));
        }
    }
};
