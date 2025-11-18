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
        // Update all existing 'vendor' records to 'supplier' in the contacts table
        DB::table('contacts')
            ->where('type', 'vendor')
            ->update(['type' => 'supplier']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert 'supplier' back to 'vendor' if needed
        DB::table('contacts')
            ->where('type', 'supplier')
            ->update(['type' => 'vendor']);
    }
};
