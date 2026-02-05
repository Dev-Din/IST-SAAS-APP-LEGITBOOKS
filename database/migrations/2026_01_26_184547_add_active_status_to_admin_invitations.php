<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add 'active' status to the enum (MySQL only; SQLite uses string and accepts any value)
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE admin_invitations MODIFY COLUMN status ENUM('pending', 'accepted', 'active', 'cancelled') DEFAULT 'pending'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE admin_invitations MODIFY COLUMN status ENUM('pending', 'accepted', 'cancelled') DEFAULT 'pending'");
        }
    }
};
