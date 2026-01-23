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
        // First, modify the enum column to include 'owner' - MySQL requires dropping and recreating
        Schema::table('admins', function (Blueprint $table) {
            // For MySQL, we need to modify the column type first
            if (DB::getDriverName() === 'mysql') {
                DB::statement("ALTER TABLE admins MODIFY COLUMN role ENUM('superadmin', 'owner', 'subadmin') DEFAULT 'subadmin'");
            } else {
                // For other databases, use the standard change method
                $table->enum('role', ['superadmin', 'owner', 'subadmin'])->default('subadmin')->change();
            }
        });

        // Now update existing superadmin records to owner
        DB::table('admins')->where('role', 'superadmin')->update(['role' => 'owner']);

        // Update Spatie roles table
        DB::table('roles')->where('name', 'superadmin')->where('guard_name', 'admin')->update(['name' => 'owner']);

        // Finally, remove 'superadmin' from the enum
        Schema::table('admins', function (Blueprint $table) {
            if (DB::getDriverName() === 'mysql') {
                DB::statement("ALTER TABLE admins MODIFY COLUMN role ENUM('owner', 'subadmin') DEFAULT 'subadmin'");
            } else {
                $table->enum('role', ['owner', 'subadmin'])->default('subadmin')->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Update existing owner records back to superadmin
        DB::table('admins')->where('role', 'owner')->update(['role' => 'superadmin']);

        // Update Spatie roles table
        DB::table('roles')->where('name', 'owner')->where('guard_name', 'admin')->update(['name' => 'superadmin']);

        // Modify the enum column back
        Schema::table('admins', function (Blueprint $table) {
            if (DB::getDriverName() === 'mysql') {
                DB::statement("ALTER TABLE admins MODIFY COLUMN role ENUM('superadmin', 'subadmin') DEFAULT 'subadmin'");
            } else {
                $table->enum('role', ['superadmin', 'subadmin'])->default('subadmin')->change();
            }
        });
    }
};
