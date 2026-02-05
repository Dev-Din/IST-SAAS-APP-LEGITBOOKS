<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Services\MpesaStkService;
use App\Services\PaymentService;
use App\Services\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncPendingMpesaPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mpesa:sync-pending 
                            {--limit=10 : Maximum number of payments to check}
                            {--checkout-request-id= : Check specific payment by checkout request ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Query Daraja API to sync pending M-Pesa payments and update their status';

    /**
     * Execute the console command.
     */
    public function handle(MpesaStkService $mpesaService, PaymentService $paymentService, TenantContext $tenantContext): int
    {
        $this->info('Syncing pending M-Pesa payments from Daraja API...');
        $this->newLine();

        $checkoutRequestId = $this->option('checkout-request-id');

        if ($checkoutRequestId) {
            // Check specific payment
            $payment = Payment::where('checkout_request_id', $checkoutRequestId)
                ->where('transaction_status', 'pending')
                ->first();

            if (! $payment) {
                $this->error("Payment with checkout request ID '{$checkoutRequestId}' not found or already processed.");

                return Command::FAILURE;
            }

            $this->syncPayment($payment, $mpesaService, $paymentService, $tenantContext);
        } else {
            // Check all pending payments
            $limit = (int) $this->option('limit');
            $payments = Payment::where('transaction_status', 'pending')
                ->whereNotNull('checkout_request_id')
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            if ($payments->isEmpty()) {
                $this->info('No pending payments found.');

                return Command::SUCCESS;
            }

            $this->info("Found {$payments->count()} pending payment(s). Checking with Daraja API...");
            $this->newLine();

            $synced = 0;
            $failed = 0;

            foreach ($payments as $payment) {
                if ($this->syncPayment($payment, $mpesaService, $paymentService, $tenantContext)) {
                    $synced++;
                } else {
                    $failed++;
                }
            }

            $this->newLine();
            $this->info("Sync complete: {$synced} updated, {$failed} still pending or failed.");
        }

        return Command::SUCCESS;
    }

    /**
     * Sync a single payment with Daraja API
     */
    protected function syncPayment(Payment $payment, MpesaStkService $mpesaService, PaymentService $paymentService, TenantContext $tenantContext): bool
    {
        $checkoutRequestId = $payment->checkout_request_id;

        $this->line("Checking: {$checkoutRequestId} (Amount: ".number_format($payment->amount, 2).' KES)');

        $queryResult = $mpesaService->querySTKPushStatus($checkoutRequestId);

        if (! $queryResult['success']) {
            $this->warn('  ❌ Query failed: '.($queryResult['error'] ?? 'Unknown error'));

            return false;
        }

        if (isset($queryResult['is_paid']) && $queryResult['is_paid']) {
            // Payment was successful
            try {
                DB::beginTransaction();

                $payment->update([
                    'transaction_status' => 'completed',
                    'reference' => $queryResult['checkout_request_id'] ?? $checkoutRequestId,
                    'mpesa_receipt' => $payment->mpesa_receipt ?? $queryResult['mpesa_receipt'] ?? null,
                ]);

                // Activate subscription if this is a subscription payment
                if ($payment->subscription_id) {
                    $subscription = $payment->subscription;
                    $subscription->update([
                        'status' => 'active',
                        'started_at' => now(),
                        'ends_at' => now()->addMonth(),
                        'next_billing_at' => now()->addMonth(),
                    ]);

                    $this->info("  ✅ Payment completed! Subscription activated: {$subscription->plan}");
                } else {
                    // Invoice payment: allocate to invoice and update invoice status (same as callback)
                    if ($payment->invoice_id) {
                        // Ensure payment has account_id (M-Pesa account) for journal entries
                        if (! $payment->account_id) {
                            $tenant = $payment->tenant;
                            $mpesaAccount = \App\Models\Account::where('tenant_id', $tenant->id)
                                ->where('type', 'mpesa')
                                ->first();

                            if (! $mpesaAccount) {
                                $cashAccount = \App\Models\ChartOfAccount::where('tenant_id', $tenant->id)
                                    ->where('code', '1400')
                                    ->first();

                                if ($cashAccount) {
                                    $mpesaAccount = \App\Models\Account::create([
                                        'tenant_id' => $tenant->id,
                                        'name' => 'M-Pesa',
                                        'type' => 'mpesa',
                                        'chart_of_account_id' => $cashAccount->id,
                                        'is_active' => true,
                                    ]);
                                }
                            }

                            if ($mpesaAccount) {
                                $payment->account_id = $mpesaAccount->id;
                                $payment->save();
                            }
                        }

                        $invoice = $payment->invoice;
                        $invoice->load('paymentAllocations');
                        $existingAllocation = $invoice->paymentAllocations()
                            ->where('payment_id', $payment->id)
                            ->first();

                        if (! $existingAllocation) {
                            $allocatedAmount = min($payment->amount, $invoice->getOutstandingAmount());
                            if ($allocatedAmount > 0) {
                                $tenantContext->setTenant($payment->tenant);
                                $canJournal = $payment->account_id && $payment->account && $payment->account->chartOfAccount;
                                if ($canJournal) {
                                    try {
                                        $paymentService->processPayment($payment, [
                                            ['invoice_id' => $invoice->id, 'amount' => $allocatedAmount],
                                        ]);
                                    } catch (\Throwable $e) {
                                        $canJournal = false;
                                        Log::warning('Sync: processPayment failed, allocating without journal', [
                                            'payment_id' => $payment->id,
                                            'error' => $e->getMessage(),
                                        ]);
                                    }
                                }
                                if (! $canJournal) {
                                    \App\Models\PaymentAllocation::create([
                                        'payment_id' => $payment->id,
                                        'invoice_id' => $invoice->id,
                                        'amount' => $allocatedAmount,
                                    ]);
                                    $this->warn('  ⚠️  Invoice updated; journal skipped (no M-Pesa/Cash account). Add Chart of Account 1400 for full books.');
                                }
                                $outstanding = $invoice->fresh()->getOutstandingAmount();
                                if ($outstanding <= 0) {
                                    $invoice->update(['status' => 'paid', 'payment_status' => 'paid']);
                                } elseif ($outstanding < $invoice->total) {
                                    $invoice->update(['payment_status' => 'partial']);
                                }
                                $this->info("  ✅ Payment completed! Invoice {$invoice->invoice_number} marked paid.");
                            } else {
                                $this->info('  ✅ Payment completed!');
                            }
                        } else {
                            $this->info('  ✅ Payment completed! (already allocated)');
                        }
                    } else {
                        $this->info('  ✅ Payment completed!');
                    }
                }

                DB::commit();

                return true;
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error('  ❌ Failed to update payment: '.$e->getMessage());
                Log::error('Failed to sync payment from query', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);

                return false;
            }
        } elseif (isset($queryResult['result_code']) && $queryResult['result_code'] != '0') {
            // Payment failed or cancelled
            $payment->update([
                'transaction_status' => 'failed',
            ]);
            $this->warn('  ⚠️  Payment failed: '.($queryResult['result_desc'] ?? 'Unknown reason'));

            return false;
        } else {
            // Still pending
            $this->line('  ⏳ Still pending...');

            return false;
        }
    }
}
