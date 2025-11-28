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
        Schema::table('payments', function (Blueprint $table) {
            // Add user_id if it doesn't exist (for tracking which user initiated payment)
            if (!Schema::hasColumn('payments', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('tenant_id')->constrained()->onDelete('set null');
            }
            
            // Add currency field (default KES)
            if (!Schema::hasColumn('payments', 'currency')) {
                $table->string('currency', 3)->default('KES')->after('amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
            
            if (Schema::hasColumn('payments', 'currency')) {
                $table->dropColumn('currency');
            }
        });
    }
};
