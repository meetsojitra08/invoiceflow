<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider'); // 'stripe'
            $table->string('provider_event_id');
            $table->string('event_type');
            $table->jsonb('payload');
            $table->string('status')->default('pending'); // pending, processed, failed, ignored
            $table->timestampTz('processed_at')->nullable();
            $table->text('error')->nullable();
            $table->timestampsTz();

            $table->unique(['provider', 'provider_event_id']);
            $table->index(['provider', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
