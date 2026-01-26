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
        // Add 'active' status to the enum
        DB::statement("ALTER TABLE admin_invitations MODIFY COLUMN status ENUM('pending', 'accepted', 'active', 'cancelled') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'active' status from the enum (revert to original)
        DB::statement("ALTER TABLE admin_invitations MODIFY COLUMN status ENUM('pending', 'accepted', 'cancelled') DEFAULT 'pending'");
    }
};
