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
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('description');
            $table->decimal('quantity', 10, 2);
            $table->bigInteger('unit_price'); // minor units
            $table->decimal('tax_rate', 5, 2)->default(0.00);
            $table->bigInteger('discount')->default(0); // minor units
            $table->bigInteger('line_total'); // minor units
            $table->unsignedInteger('position')->default(0);
            $table->timestampsTz();

            $table->index(['invoice_id', 'position']);
        });

        DB::statement('ALTER TABLE invoice_items ADD CONSTRAINT invoice_items_quantity_check CHECK (quantity > 0)');
        DB::statement('ALTER TABLE invoice_items ADD CONSTRAINT invoice_items_unit_price_check CHECK (unit_price >= 0)');
        DB::statement('ALTER TABLE invoice_items ADD CONSTRAINT invoice_items_discount_check CHECK (discount >= 0)');
        DB::statement('ALTER TABLE invoice_items ADD CONSTRAINT invoice_items_line_total_check CHECK (line_total >= 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
