<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('invoice_counters')) {
            // Create table if it doesn't exist
            Schema::create('invoice_counters', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->smallInteger('year');
                $table->unsignedInteger('sequence')->default(0);
                $table->timestamps();
                
                // Unique constraint: one counter per tenant per year
                $table->unique(['tenant_id', 'year']);
            });
        } else {
            // Update existing table
            Schema::table('invoice_counters', function (Blueprint $table) {
                // Check if unique constraint exists before trying to drop it
                // Note: We can't drop it if it's used by a foreign key, so we'll skip it
                // Use database-agnostic method to check for index
                $indexExists = false;
                try {
                    if (DB::getDriverName() === 'mysql') {
                        $indexes = DB::select("SHOW INDEX FROM invoice_counters WHERE Key_name = 'invoice_counters_tenant_id_unique'");
                        $indexExists = !empty($indexes);
                    } else {
                        // For SQLite and other databases, try to drop and catch exception
                        $indexExists = true; // Assume exists, will catch if not
                    }
                } catch (\Exception $e) {
                    $indexExists = false;
                }
                
                if ($indexExists) {
                    // Check if it's used by a foreign key
                    $foreignKeys = DB::select("
                        SELECT CONSTRAINT_NAME 
                        FROM information_schema.KEY_COLUMN_USAGE 
                        WHERE TABLE_SCHEMA = DATABASE() 
                        AND TABLE_NAME = 'invoice_counters' 
                        AND COLUMN_NAME = 'tenant_id' 
                        AND CONSTRAINT_NAME != 'PRIMARY'
                    ");
                    // Only drop if not used by foreign key
                    if (empty($foreignKeys)) {
                        try {
                            $table->dropUnique(['tenant_id']);
                        } catch (\Exception $e) {
                            // Constraint might be used elsewhere, continue
                        }
                    }
                }
                
                // Add year column if it doesn't exist
                if (!Schema::hasColumn('invoice_counters', 'year')) {
                    $table->smallInteger('year')->default(now()->year)->after('tenant_id');
                }
                
                // Add sequence column if it doesn't exist (or migrate from last_number)
                if (!Schema::hasColumn('invoice_counters', 'sequence')) {
                    if (Schema::hasColumn('invoice_counters', 'last_number')) {
                        // Add sequence column and copy data
                        $table->unsignedInteger('sequence')->default(0)->after('year');
                    } else {
                        $table->unsignedInteger('sequence')->default(0)->after('year');
                    }
                }
            });

            // Migrate data from last_number to sequence if needed
            if (Schema::hasColumn('invoice_counters', 'last_number') && Schema::hasColumn('invoice_counters', 'sequence')) {
                DB::statement('UPDATE invoice_counters SET sequence = last_number WHERE sequence = 0 AND last_number > 0');
            }

            // Set year for existing records if not set
            if (Schema::hasColumn('invoice_counters', 'year')) {
                DB::statement('UPDATE invoice_counters SET year = ? WHERE year = 0 OR year IS NULL', [now()->year]);
            }

            // Drop last_number column if it exists and sequence exists
            Schema::table('invoice_counters', function (Blueprint $table) {
                if (Schema::hasColumn('invoice_counters', 'last_number') && Schema::hasColumn('invoice_counters', 'sequence')) {
                    $table->dropColumn('last_number');
                }
                
                // Add unique constraint on tenant_id + year if it doesn't exist
                // Use database-agnostic method
                $uniqueExists = false;
                try {
                    if (DB::getDriverName() === 'mysql') {
                        $indexes = DB::select("SHOW INDEX FROM invoice_counters WHERE Key_name = 'invoice_counters_tenant_id_year_unique'");
                        $uniqueExists = !empty($indexes);
                    } else {
                        // For SQLite, check via schema
                        $uniqueExists = Schema::hasIndex('invoice_counters', 'invoice_counters_tenant_id_year_unique');
                    }
                } catch (\Exception $e) {
                    $uniqueExists = false;
                }
                
                if (!$uniqueExists) {
                    try {
                        $table->unique(['tenant_id', 'year'], 'invoice_counters_tenant_id_year_unique');
                    } catch (\Exception $e) {
                        // Index might already exist, continue
                    }
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Don't drop the table, just revert structure if needed
        Schema::table('invoice_counters', function (Blueprint $table) {
            if (Schema::hasIndex('invoice_counters', 'invoice_counters_tenant_id_year_unique')) {
                $table->dropUnique('invoice_counters_tenant_id_year_unique');
            }
            
            if (Schema::hasColumn('invoice_counters', 'year')) {
                $table->dropColumn('year');
            }
            
            if (!Schema::hasColumn('invoice_counters', 'last_number') && Schema::hasColumn('invoice_counters', 'sequence')) {
                $table->unsignedInteger('last_number')->default(0)->after('tenant_id');
                DB::statement('UPDATE invoice_counters SET last_number = sequence');
                $table->dropColumn('sequence');
            }
        });
    }
};
