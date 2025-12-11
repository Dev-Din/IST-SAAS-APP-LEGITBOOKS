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
        Schema::table('payment_allocations', function (Blueprint $table) {
            // Make invoice_id nullable since we'll also support bills
            if (Schema::hasColumn('payment_allocations', 'invoice_id')) {
                $table->foreignId('invoice_id')->nullable()->change();
            }
            
            // Add bill_id column
            if (!Schema::hasColumn('payment_allocations', 'bill_id')) {
                $table->foreignId('bill_id')->nullable()->after('invoice_id')->constrained('bills')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_allocations', function (Blueprint $table) {
            if (Schema::hasColumn('payment_allocations', 'bill_id')) {
                $table->dropForeign(['bill_id']);
                $table->dropColumn('bill_id');
            }
            
            // Revert invoice_id to not nullable if needed
            // Note: This might fail if there are null values, so we'll leave it nullable
        });
    }
};
