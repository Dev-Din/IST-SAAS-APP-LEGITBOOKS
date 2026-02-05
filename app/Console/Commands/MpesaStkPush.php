<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\MpesaStkService;
use App\Services\TenantContext;
use Illuminate\Console\Command;

class MpesaStkPush extends Command
{
    protected $signature = 'mpesa:stk-push {tenant_hash} {phone} {amount}';

    protected $description = 'Initiate a real M-Pesa STK push (sandbox/production)';

    public function handle(MpesaStkService $stkService, TenantContext $tenantContext): int
    {
        $tenantHash = $this->argument('tenant_hash');
        $phone = $this->argument('phone');
        $amount = (float) $this->argument('amount');

        $tenant = Tenant::where('tenant_hash', $tenantHash)->first();

        if (! $tenant) {
            $this->error("Tenant not found with hash: {$tenantHash}");

            return Command::FAILURE;
        }

        if (! $stkService->isConfigured()) {
            $this->error('M-Pesa is not configured. Set MPESA_CONSUMER_KEY, MPESA_CONSUMER_SECRET, MPESA_PASSKEY, MPESA_SHORTCODE (and MPESA_CALLBACK_BASE for callback).');

            return Command::FAILURE;
        }

        $tenantContext->setTenant($tenant);

        $this->info("Initiating STK push: KES {$amount} to {$phone}...");

        $result = $stkService->initiateSTKPush([
            'phone_number' => $phone,
            'amount' => $amount,
            'account_reference' => 'STK-TEST-'.now()->format('YmdHis'),
            'transaction_desc' => 'Test STK push KES '.$amount,
        ]);

        if ($result['success']) {
            $this->info('STK push sent! Check your phone ('.$phone.') for the M-Pesa prompt.');
            $this->line('CheckoutRequestID: '.($result['checkoutRequestID'] ?? 'N/A'));
            $this->line('Message: '.($result['customerMessage'] ?? ''));
        } else {
            $this->error('STK push failed: '.($result['error'] ?? 'Unknown error'));
            if (! empty($result['callback_url'])) {
                $this->line('Callback URL: '.$result['callback_url']);
            }

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
