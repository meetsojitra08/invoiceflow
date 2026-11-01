<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->restrictOnDelete();
            $table->string('number');
            $table->string('status')->default('draft');
            $table->date('issue_date');
            $table->date('due_date');
            $table->char('currency', 3)->default('INR');
            $table->bigInteger('subtotal');
            $table->bigInteger('discount_total')->default(0);
            $table->bigInteger('tax_total')->default(0);
            $table->bigInteger('total');
            $table->bigInteger('amount_paid')->default(0);
            $table->text('notes')->nullable();
            $table->string('public_token', 64)->unique();
            $table->timestampTz('sent_at')->nullable();
            $table->timestampTz('paid_at')->nullable();
            $table->timestampTz('voided_at')->nullable();
            $table->string('void_reason')->nullable();
            $table->timestampsTz();

            $table->unique(['company_id', 'number']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'due_date']);
            $table->index(['company_id', 'client_id']);
            $table->index(['company_id', 'issue_date']);
        });

        DB::statement('ALTER TABLE invoices ADD CONSTRAINT invoices_subtotal_check CHECK (subtotal >= 0)');
        DB::statement('ALTER TABLE invoices ADD CONSTRAINT invoices_discount_total_check CHECK (discount_total >= 0)');
        DB::statement('ALTER TABLE invoices ADD CONSTRAINT invoices_tax_total_check CHECK (tax_total >= 0)');
        DB::statement('ALTER TABLE invoices ADD CONSTRAINT invoices_total_check CHECK (total >= 0)');
        DB::statement('ALTER TABLE invoices ADD CONSTRAINT invoices_amount_paid_check CHECK (amount_paid >= 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};

