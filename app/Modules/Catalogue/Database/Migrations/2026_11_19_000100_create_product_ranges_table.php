<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Product Ranges (FR-13): each Product belongs to one, and Pricing keeps the
 * range's Platform Markup in its own table. A range is Active or Retired; a
 * retired range stays, with its History, and takes no new Products. Two
 * active ranges never share a name, whatever its case.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_ranges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 120);
            $table->string('compliance', 32);
            $table->string('status', 16);
            $table->timestampsTz();
        });

        DB::statement("ALTER TABLE product_ranges ADD CONSTRAINT product_ranges_compliance_check CHECK (compliance IN ('none', 'ncc_type_approval', 'soncap'))");
        DB::statement("ALTER TABLE product_ranges ADD CONSTRAINT product_ranges_status_check CHECK (status IN ('active', 'retired'))");
        DB::statement("ALTER TABLE product_ranges ADD CONSTRAINT product_ranges_name_check CHECK (name = btrim(name) AND name <> '')");
        DB::statement("CREATE UNIQUE INDEX product_ranges_active_name_unique ON product_ranges (lower(name)) WHERE status = 'active'");
    }

    public function down(): void
    {
        Schema::dropIfExists('product_ranges');
    }
};
