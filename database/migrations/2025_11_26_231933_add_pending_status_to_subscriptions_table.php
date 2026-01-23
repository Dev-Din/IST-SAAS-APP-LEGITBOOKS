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
        // Modify the enum to include 'pending'
        DB::statement("ALTER TABLE subscriptions MODIFY COLUMN status ENUM('active', 'trial', 'cancelled', 'expired', 'pending') DEFAULT 'trial'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original enum (but first update any 'pending' subscriptions)
        DB::statement("UPDATE subscriptions SET status = 'trial' WHERE status = 'pending'");
        DB::statement("ALTER TABLE subscriptions MODIFY COLUMN status ENUM('active', 'trial', 'cancelled', 'expired') DEFAULT 'trial'");
    }
};
