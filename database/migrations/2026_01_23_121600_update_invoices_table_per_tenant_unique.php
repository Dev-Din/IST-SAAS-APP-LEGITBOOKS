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
        Schema::table('invoices', function (Blueprint $table) {
            // Drop the global unique constraint on invoice_number
            $table->dropUnique(['invoice_number']);

            // Add composite unique constraint: tenant_id + invoice_number
            // This allows each tenant to have their own invoice numbers starting from 001
            $table->unique(['tenant_id', 'invoice_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Drop the composite unique constraint
            $table->dropUnique(['tenant_id', 'invoice_number']);

            // Restore global unique constraint on invoice_number
            $table->unique(['invoice_number']);
        });
    }
};
