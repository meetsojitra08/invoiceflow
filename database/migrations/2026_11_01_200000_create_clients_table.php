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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('billing_address')->nullable();
            $table->string('tax_number')->nullable();
            $table->boolean('send_reminders')->default(true);
            $table->softDeletesTz();
            $table->timestampsTz();

            $table->index(['company_id', 'name']);
            $table->index(['company_id', 'email']);
        });

        // PostgreSQL partial unique index for lower(email) per company among non-deleted clients
        DB::statement('CREATE UNIQUE INDEX clients_company_email_unique ON clients (company_id, lower(email)) WHERE deleted_at IS NULL AND email IS NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
