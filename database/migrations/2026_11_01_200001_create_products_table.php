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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->bigInteger('unit_price'); // minor units
            $table->decimal('tax_rate', 5, 2)->default(0.00); // 0.00 to 100.00 %
            $table->boolean('is_active')->default(true);
            $table->softDeletesTz();
            $table->timestampsTz();

            $table->index(['company_id', 'name']);
            $table->index(['company_id', 'is_active']);
        });

        // PostgreSQL CHECK constraints on money and tax rate
        DB::statement('ALTER TABLE products ADD CONSTRAINT products_unit_price_check CHECK (unit_price >= 0)');
        DB::statement('ALTER TABLE products ADD CONSTRAINT products_tax_rate_check CHECK (tax_rate >= 0 AND tax_rate <= 100)');

        // Partial unique index for lower(name) per company among non-deleted products
        DB::statement('CREATE UNIQUE INDEX products_company_name_unique ON products (company_id, lower(name)) WHERE deleted_at IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

