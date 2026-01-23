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
            // Add invoice_id if it doesn't exist
            if (! Schema::hasColumn('payments', 'invoice_id')) {
                $table->foreignId('invoice_id')->nullable()->after('tenant_id')->constrained()->onDelete('set null');
            }

            // Add STK Push specific fields
            if (! Schema::hasColumn('payments', 'phone')) {
                $table->string('phone')->nullable()->after('contact_id');
            }

            if (! Schema::hasColumn('payments', 'mpesa_receipt')) {
                $table->string('mpesa_receipt')->nullable()->after('reference');
            }

            if (! Schema::hasColumn('payments', 'transaction_status')) {
                $table->enum('transaction_status', ['pending', 'completed', 'failed', 'cancelled'])->default('pending')->after('mpesa_receipt');
            }

            if (! Schema::hasColumn('payments', 'raw_callback')) {
                $table->json('raw_callback')->nullable()->after('transaction_status');
            }

            if (! Schema::hasColumn('payments', 'checkout_request_id')) {
                $table->string('checkout_request_id')->nullable()->unique()->after('raw_callback');
            }

            if (! Schema::hasColumn('payments', 'merchant_request_id')) {
                $table->string('merchant_request_id')->nullable()->after('checkout_request_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'invoice_id')) {
                $table->dropForeign(['invoice_id']);
                $table->dropColumn('invoice_id');
            }

            if (Schema::hasColumn('payments', 'phone')) {
                $table->dropColumn('phone');
            }

            if (Schema::hasColumn('payments', 'mpesa_receipt')) {
                $table->dropColumn('mpesa_receipt');
            }

            if (Schema::hasColumn('payments', 'transaction_status')) {
                $table->dropColumn('transaction_status');
            }

            if (Schema::hasColumn('payments', 'raw_callback')) {
                $table->dropColumn('raw_callback');
            }

            if (Schema::hasColumn('payments', 'checkout_request_id')) {
                $table->dropColumn('checkout_request_id');
            }

            if (Schema::hasColumn('payments', 'merchant_request_id')) {
                $table->dropColumn('merchant_request_id');
            }
        });
    }
};
