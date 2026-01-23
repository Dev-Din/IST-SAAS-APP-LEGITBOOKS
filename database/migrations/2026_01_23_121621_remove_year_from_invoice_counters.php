<?php

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
        // First, consolidate existing counters per tenant
        // For each tenant, find the maximum sequence across all years
        $tenants = DB::table('invoice_counters')
            ->select('tenant_id')
            ->distinct()
            ->get();

        foreach ($tenants as $tenant) {
            // Get the maximum sequence for this tenant across all years
            $maxSequence = DB::table('invoice_counters')
                ->where('tenant_id', $tenant->tenant_id)
                ->max('sequence');

            // Delete all existing counters for this tenant
            DB::table('invoice_counters')
                ->where('tenant_id', $tenant->tenant_id)
                ->delete();

            // Create a single counter with the maximum sequence
            DB::table('invoice_counters')->insert([
                'tenant_id' => $tenant->tenant_id,
                'sequence' => $maxSequence ?? 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Now update the table structure
        Schema::table('invoice_counters', function (Blueprint $table) {
            // Drop the composite unique constraint on tenant_id + year
            if (Schema::hasIndex('invoice_counters', 'invoice_counters_tenant_id_year_unique')) {
                $table->dropUnique('invoice_counters_tenant_id_year_unique');
            }

            // Drop the year column
            if (Schema::hasColumn('invoice_counters', 'year')) {
                $table->dropColumn('year');
            }

            // Add unique constraint on tenant_id only (one counter per tenant)
            $table->unique('tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_counters', function (Blueprint $table) {
            // Drop the unique constraint on tenant_id
            $table->dropUnique(['tenant_id']);

            // Add year column back
            $table->smallInteger('year')->default(now()->year)->after('tenant_id');

            // Add composite unique constraint back
            $table->unique(['tenant_id', 'year'], 'invoice_counters_tenant_id_year_unique');
        });

        // Note: Data migration reversal is complex - we can't restore year-based counters
        // This is a one-way migration in practice
    }
};
