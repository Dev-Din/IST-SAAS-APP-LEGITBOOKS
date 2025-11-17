<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\MpesaService;
use App\Services\TenantContext;
use Illuminate\Console\Command;

class MpesaSimulate extends Command
{
    protected $signature = 'mpesa:simulate {tenant_hash} {phone} {amount}';

    protected $description = 'Simulate M-Pesa payment for development';

    public function handle(MpesaService $mpesaService, TenantContext $tenantContext)
    {
        $tenantHash = $this->argument('tenant_hash');
        $phone = $this->argument('phone');
        $amount = (float) $this->argument('amount');

        $tenant = Tenant::where('tenant_hash', $tenantHash)->first();

        if (!$tenant) {
            $this->error("Tenant not found with hash: {$tenantHash}");
            return Command::FAILURE;
        }

        $tenantContext->setTenant($tenant);

        $result = $mpesaService->simulatePayment($phone, $amount);

        if ($result['success']) {
            $this->info("M-Pesa payment simulated successfully!");
            $this->info("Payment Number: {$result['payment']->payment_number}");
            $this->info("Amount: {$amount}");
        } else {
            $this->error("Failed to simulate payment");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
