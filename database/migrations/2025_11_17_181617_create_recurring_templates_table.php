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
        Schema::create('recurring_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->foreignId('contact_id')->constrained()->onDelete('cascade');
            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'yearly', 'custom']);
            $table->string('cron_expression')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('invoice_template')->nullable(); // stores invoice data structure
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_templates');
    }
};
