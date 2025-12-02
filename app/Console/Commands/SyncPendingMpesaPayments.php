<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Services\MpesaStkService;
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
    public function handle(MpesaStkService $mpesaService): int
    {
        $this->info('Syncing pending M-Pesa payments from Daraja API...');
        $this->newLine();

        $checkoutRequestId = $this->option('checkout-request-id');
        
        if ($checkoutRequestId) {
            // Check specific payment
            $payment = Payment::where('checkout_request_id', $checkoutRequestId)
                ->where('transaction_status', 'pending')
                ->first();

            if (!$payment) {
                $this->error("Payment with checkout request ID '{$checkoutRequestId}' not found or already processed.");
                return Command::FAILURE;
            }

            $this->syncPayment($payment, $mpesaService);
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
                if ($this->syncPayment($payment, $mpesaService)) {
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
    protected function syncPayment(Payment $payment, MpesaStkService $mpesaService): bool
    {
        $checkoutRequestId = $payment->checkout_request_id;
        
        $this->line("Checking: {$checkoutRequestId} (Amount: " . number_format($payment->amount, 2) . " KES)");

        $queryResult = $mpesaService->querySTKPushStatus($checkoutRequestId);

        if (!$queryResult['success']) {
            $this->warn("  ❌ Query failed: " . ($queryResult['error'] ?? 'Unknown error'));
            return false;
        }

        if (isset($queryResult['is_paid']) && $queryResult['is_paid']) {
            // Payment was successful
            try {
                DB::beginTransaction();

                $payment->update([
                    'transaction_status' => 'completed',
                    'reference' => $queryResult['checkout_request_id'] ?? $checkoutRequestId,
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
                    $this->info("  ✅ Payment completed!");
                }

                DB::commit();
                return true;
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("  ❌ Failed to update payment: " . $e->getMessage());
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
            $this->warn("  ⚠️  Payment failed: " . ($queryResult['result_desc'] ?? 'Unknown reason'));
            return false;
        } else {
            // Still pending
            $this->line("  ⏳ Still pending...");
            return false;
        }
    }
}

