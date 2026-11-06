<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * The Support duties switch on an Operations account (FR-52): while it is On,
 * the account holds the Support duties group in the capability registry.
 * Every account, existing or new, starts Off (least privilege); an Admin
 * turns it on from the Staff page, audited. It is the only capability stored
 * per person.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_members', function (Blueprint $table) {
            $table->boolean('support_duties')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('staff_members', function (Blueprint $table) {
            $table->dropColumn('support_duties');
        });
    }
};
