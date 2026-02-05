<?php

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
        // Skip if bill_counters already created by 2025_12_11_193616_create_bill_counters_table
        if (Schema::hasTable('bill_counters')) {
            return;
        }

        Schema::create('bill_counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->smallInteger('year');
            $table->unsignedInteger('sequence')->default(0);
            $table->timestamps();

            // Unique constraint: one counter per tenant per year
            $table->unique(['tenant_id', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bill_counters');
    }
};
