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
        // For each tenant, find the maximum counter across all years
        $tenants = DB::table('bill_counters')
            ->select('tenant_id')
            ->distinct()
            ->get();

        foreach ($tenants as $tenant) {
            // Get the maximum counter for this tenant across all years
            $maxCounter = DB::table('bill_counters')
                ->where('tenant_id', $tenant->tenant_id)
                ->max('counter');

            // Delete all existing counters for this tenant
            DB::table('bill_counters')
                ->where('tenant_id', $tenant->tenant_id)
                ->delete();

            // Create a single counter with the maximum counter value
            DB::table('bill_counters')->insert([
                'tenant_id' => $tenant->tenant_id,
                'counter' => $maxCounter ?? 0,
                'prefix' => 'Bill',
                'format' => 'Bill-{COUNTER}',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Now update the table structure
        Schema::table('bill_counters', function (Blueprint $table) {
            // Drop the composite unique constraint on tenant_id + year
            $table->dropUnique(['tenant_id', 'year']);

            // Drop the year column
            if (Schema::hasColumn('bill_counters', 'year')) {
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
        Schema::table('bill_counters', function (Blueprint $table) {
            // Drop the unique constraint on tenant_id
            $table->dropUnique(['tenant_id']);

            // Add year column back
            $table->integer('year')->default(now()->year)->after('tenant_id');

            // Add composite unique constraint back
            $table->unique(['tenant_id', 'year']);
        });

        // Note: Data migration reversal is complex - we can't restore year-based counters
        // This is a one-way migration in practice
    }
};
