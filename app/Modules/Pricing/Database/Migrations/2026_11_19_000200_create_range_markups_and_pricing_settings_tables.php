<?php

use App\Modules\Shared\Contracts\Clock;
use App\Modules\Shared\Support\Id;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Pricing's rules that Admin sets (FR-13, AD-7). Each Product Range has one
 * Platform Markup, a percentage over Supplier Cost; the range itself belongs
 * to Catalogue, so its row cannot be deleted while a markup names it. The
 * margin floor is one row of `pricing_settings`, inserted here once at 8%;
 * after that only the SetMarginFloor Action changes it. Percentages are
 * numeric(7,4), and neither may be below zero (edge-case row 20).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('range_markups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_range_id')->unique()->constrained('product_ranges')->restrictOnDelete();
            $table->decimal('markup_percent', 7, 4);
            $table->timestampsTz();
        });

        DB::statement('ALTER TABLE range_markups ADD CONSTRAINT range_markups_markup_percent_check CHECK (markup_percent >= 0)');

        Schema::create('pricing_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key', 64)->unique();
            $table->decimal('percent', 7, 4);
            $table->timestampsTz();
        });

        DB::statement("ALTER TABLE pricing_settings ADD CONSTRAINT pricing_settings_key_check CHECK (key IN ('margin_floor'))");
        DB::statement('ALTER TABLE pricing_settings ADD CONSTRAINT pricing_settings_percent_check CHECK (percent >= 0)');

        $now = app(Clock::class)->now();

        DB::table('pricing_settings')->insert([
            'id' => Id::generate(),
            'key' => 'margin_floor',
            'percent' => '8.0000',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_settings');
        Schema::dropIfExists('range_markups');
    }
};
