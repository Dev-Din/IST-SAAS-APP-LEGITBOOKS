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
        Schema::create('bill_counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->integer('year');
            $table->integer('counter')->default(0);
            $table->string('prefix')->default('BILL');
            $table->string('format')->default('BILL-{YEAR}-{COUNTER}');
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
