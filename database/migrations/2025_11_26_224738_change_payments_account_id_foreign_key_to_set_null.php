<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Changes the payments.account_id foreign key constraint from 'restrict' to 'set null'
     * This allows accounts to be deleted while preserving payment history.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Drop the existing foreign key constraint
            $table->dropForeign(['account_id']);
        });

        // Make account_id nullable if it's not already
        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id')->nullable()->change();
        });

        // Re-add the foreign key with 'set null' on delete
        Schema::table('payments', function (Blueprint $table) {
            $table->foreign('account_id')
                ->references('id')
                ->on('accounts')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Drop the foreign key
            $table->dropForeign(['account_id']);
        });

        // Make account_id not nullable and restore the restrict constraint
        Schema::table('payments', function (Blueprint $table) {
            // First, ensure no null values exist
            DB::statement('UPDATE payments SET account_id = (SELECT id FROM accounts LIMIT 1) WHERE account_id IS NULL');

            $table->unsignedBigInteger('account_id')->nullable(false)->change();
        });

        // Re-add the foreign key with 'restrict' on delete
        Schema::table('payments', function (Blueprint $table) {
            $table->foreign('account_id')
                ->references('id')
                ->on('accounts')
                ->onDelete('restrict');
        });
    }
};
