<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Staff accounts for the console's `staff` guard (AD-18). Integrator accounts
 * live in the Identity module's own table and can never sign in here.
 * The authenticator secret and recovery codes are encrypted by the model.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role', 16);
            $table->boolean('is_founder')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('app_authentication_secret')->nullable();
            $table->text('app_authentication_recovery_codes')->nullable();
            $table->timestampsTz();
        });

        DB::statement("ALTER TABLE staff_members ADD CONSTRAINT staff_members_role_check CHECK (role IN ('admin', 'finance', 'operations'))");
        DB::statement('ALTER TABLE staff_members ADD CONSTRAINT staff_members_email_lowercase_check CHECK (email = lower(email))');
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_members');
    }
};
