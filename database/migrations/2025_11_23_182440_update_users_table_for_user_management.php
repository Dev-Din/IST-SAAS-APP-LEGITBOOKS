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
        Schema::table('users', function (Blueprint $table) {
            // Add new columns only if they don't exist
            if (! Schema::hasColumn('users', 'first_name')) {
                $table->string('first_name')->nullable()->after('name');
            }

            if (! Schema::hasColumn('users', 'last_name')) {
                $table->string('last_name')->nullable()->after('first_name');
            }

            if (! Schema::hasColumn('users', 'role_name')) {
                $table->string('role_name')->nullable()->after('last_name');
            }

            if (! Schema::hasColumn('users', 'permissions')) {
                $table->json('permissions')->nullable()->after('role_name');
            }
        });

        // Handle unique constraint on email - drop if exists, then add composite
        try {
            $dropEmailUnique = false;
            if (DB::getDriverName() === 'mysql') {
                $indexes = DB::select("SHOW INDEX FROM users WHERE Column_name = 'email' AND Non_unique = 0");
                $dropEmailUnique = ! empty($indexes);
            } else {
                $dropEmailUnique = Schema::hasIndex('users', 'users_email_unique');
            }
            if ($dropEmailUnique) {
                Schema::table('users', function (Blueprint $table) {
                    $table->dropUnique(['email']);
                });
            }
        } catch (\Exception $e) {
            // Index might not exist or already dropped, continue
        }

        // Add composite unique index on tenant_id + email if it doesn't exist
        try {
            $compositeExists = Schema::hasIndex('users', 'users_tenant_email_unique');
            if (! $compositeExists) {
                Schema::table('users', function (Blueprint $table) {
                    $table->unique(['tenant_id', 'email'], 'users_tenant_email_unique');
                });
            }
        } catch (\Exception $e) {
            // Index might already exist, continue
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop composite unique index if it exists
            try {
                if (Schema::hasIndex('users', 'users_tenant_email_unique')) {
                    $table->dropUnique('users_tenant_email_unique');
                }
            } catch (\Exception $e) {
                // Continue
            }

            // Remove new columns if they exist
            $columnsToDrop = [];
            if (Schema::hasColumn('users', 'permissions')) {
                $columnsToDrop[] = 'permissions';
            }
            if (Schema::hasColumn('users', 'role_name')) {
                $columnsToDrop[] = 'role_name';
            }
            if (Schema::hasColumn('users', 'last_name')) {
                $columnsToDrop[] = 'last_name';
            }
            if (Schema::hasColumn('users', 'first_name')) {
                $columnsToDrop[] = 'first_name';
            }

            if (! empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });

        // Restore unique constraint on email
        try {
            Schema::table('users', function (Blueprint $table) {
                $table->unique('email');
            });
        } catch (\Exception $e) {
            // Continue
        }
    }
};
