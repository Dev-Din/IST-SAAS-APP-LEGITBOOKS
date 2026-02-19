<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ensure invoice_counters has no 'year' column (defensive migration).
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('invoice_counters') || ! Schema::hasColumn('invoice_counters', 'year')) {
            return;
        }

        $driver = DB::getDriverName();

        // 1) Make year nullable (raw SQL to avoid doctrine/dbal)
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE invoice_counters MODIFY year SMALLINT NULL');
        } else {
            Schema::table('invoice_counters', function (Blueprint $table) {
                $table->smallInteger('year')->nullable()->change();
            });
        }

        // 2) Consolidate: one row per tenant with max sequence
        $tenants = DB::table('invoice_counters')->select('tenant_id')->distinct()->get();
        foreach ($tenants as $row) {
            $tenantId = $row->tenant_id;
            $maxSequence = (int) DB::table('invoice_counters')->where('tenant_id', $tenantId)->max('sequence');
            DB::table('invoice_counters')->where('tenant_id', $tenantId)->delete();
            DB::table('invoice_counters')->insert([
                'tenant_id' => $tenantId,
                'year' => null,
                'sequence' => $maxSequence,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 3) Add unique on tenant_id first (so FK on tenant_id can use it), then drop composite and year
        Schema::table('invoice_counters', function (Blueprint $table) {
            if (! Schema::hasIndex('invoice_counters', 'invoice_counters_tenant_id_unique')) {
                $table->unique('tenant_id', 'invoice_counters_tenant_id_unique');
            }
        });
        Schema::table('invoice_counters', function (Blueprint $table) {
            if (Schema::hasIndex('invoice_counters', 'invoice_counters_tenant_id_year_unique')) {
                $table->dropUnique('invoice_counters_tenant_id_year_unique');
            }
            if (Schema::hasColumn('invoice_counters', 'year')) {
                $table->dropColumn('year');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: target state is no year column; reversing would re-add complexity
    }
};
