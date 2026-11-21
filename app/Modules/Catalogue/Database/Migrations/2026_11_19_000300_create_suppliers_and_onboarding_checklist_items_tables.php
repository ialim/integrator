<?php

use App\Modules\Shared\Contracts\Clock;
use App\Modules\Shared\Support\Id;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Suppliers and their onboarding checklist (FR-14, FR-81). A Supplier is
 * saved Not approved with only its legal name required; every other detail
 * may be missing. Only an Admin approves one, against the checklist, or with
 * a written reason. An approval names who and when, and is never taken back
 * by an edit or a checklist change.
 *
 * The checklist is one row per item, keyed, so Stories 2.6 and 2.9 add their
 * document and bank items to the same table. The three agreement terms are
 * always required, here as well as in the Action.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('legal_name', 200);
            $table->string('trade_name', 200)->nullable();
            $table->string('cac_rc_number', 32)->nullable();
            $table->string('tin', 32)->nullable();
            $table->string('address', 500)->nullable();
            $table->string('invoice_currency', 3)->nullable();
            $table->string('payment_terms', 200)->nullable();
            $table->string('sales_contact_name', 120)->nullable();
            $table->string('sales_contact_phone', 32)->nullable();
            $table->string('sales_contact_email', 254)->nullable();
            $table->string('returns_contact_name', 120)->nullable();
            $table->string('returns_contact_phone', 32)->nullable();
            $table->string('returns_contact_email', 254)->nullable();
            $table->text('returns_terms')->nullable();
            $table->text('doa_terms')->nullable();
            $table->integer('doa_window_days')->nullable();
            $table->text('counterfeit_terms')->nullable();
            $table->string('status', 16);
            $table->foreignUuid('approved_by')->nullable()->constrained('staff_members')->restrictOnDelete();
            $table->timestampTz('approved_at')->nullable();
            $table->text('approval_reason')->nullable();
            $table->timestampsTz();
        });

        DB::statement("ALTER TABLE suppliers ADD CONSTRAINT suppliers_legal_name_check CHECK (legal_name = btrim(legal_name) AND legal_name <> '')");
        DB::statement("ALTER TABLE suppliers ADD CONSTRAINT suppliers_invoice_currency_check CHECK (invoice_currency IN ('USD', 'NGN'))");
        DB::statement('ALTER TABLE suppliers ADD CONSTRAINT suppliers_doa_window_days_check CHECK (doa_window_days BETWEEN 1 AND 9999)');
        DB::statement("ALTER TABLE suppliers ADD CONSTRAINT suppliers_status_check CHECK (status IN ('not_approved', 'approved'))");
        // Approved names who and when; Not approved names neither, nor a reason.
        DB::statement("ALTER TABLE suppliers ADD CONSTRAINT suppliers_approval_check CHECK (
            (status = 'approved' AND approved_by IS NOT NULL AND approved_at IS NOT NULL)
            OR (status = 'not_approved' AND approved_by IS NULL AND approved_at IS NULL AND approval_reason IS NULL)
        )");

        Schema::create('onboarding_checklist_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key', 64)->unique();
            $table->boolean('required');
            $table->smallInteger('position');
            $table->timestampsTz();
        });

        DB::statement("ALTER TABLE onboarding_checklist_items ADD CONSTRAINT onboarding_checklist_items_terms_required_check CHECK (
            required OR key NOT IN ('returns_terms', 'doa_terms', 'counterfeit_chargeback')
        )");

        $now = app(Clock::class)->now();
        $items = ['business_details', 'contacts', 'returns_terms', 'doa_terms', 'counterfeit_chargeback'];

        foreach ($items as $position => $key) {
            DB::table('onboarding_checklist_items')->insert([
                'id' => Id::generate(),
                'key' => $key,
                'required' => true,
                'position' => ($position + 1) * 10,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_checklist_items');
        Schema::dropIfExists('suppliers');
    }
};
