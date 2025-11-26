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
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'sent_at')) {
                $table->timestamp('sent_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('invoices', 'pdf_path')) {
                $table->string('pdf_path')->nullable()->after('sent_at');
            }
            if (!Schema::hasColumn('invoices', 'payment_token')) {
                $table->string('payment_token')->nullable()->unique()->after('pdf_path');
            }
            if (!Schema::hasColumn('invoices', 'payment_status')) {
                $table->enum('payment_status', ['pending', 'paid', 'partial', 'failed'])->nullable()->default('pending')->after('payment_token');
            }
            if (!Schema::hasColumn('invoices', 'mail_status')) {
                $table->enum('mail_status', ['pending', 'sent', 'failed'])->nullable()->default('pending')->after('payment_status');
            }
            if (!Schema::hasColumn('invoices', 'mail_message_id')) {
                $table->string('mail_message_id')->nullable()->after('mail_status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'sent_at')) {
                $table->dropColumn('sent_at');
            }
            if (Schema::hasColumn('invoices', 'pdf_path')) {
                $table->dropColumn('pdf_path');
            }
            if (Schema::hasColumn('invoices', 'payment_token')) {
                $table->dropColumn('payment_token');
            }
            if (Schema::hasColumn('invoices', 'payment_status')) {
                $table->dropColumn('payment_status');
            }
            if (Schema::hasColumn('invoices', 'mail_status')) {
                $table->dropColumn('mail_status');
            }
            if (Schema::hasColumn('invoices', 'mail_message_id')) {
                $table->dropColumn('mail_message_id');
            }
        });
    }
};
