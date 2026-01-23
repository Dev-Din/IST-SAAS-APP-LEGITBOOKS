<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            // Drop the existing unique constraint on code
            $table->dropUnique(['code']);

            // Add composite unique constraint on tenant_id and code
            // This ensures code is unique per tenant, not globally
            $table->unique(['tenant_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            // Drop the composite unique constraint
            $table->dropUnique(['tenant_id', 'code']);

            // Restore the original unique constraint on code only
            $table->unique('code');
        });
    }
};
