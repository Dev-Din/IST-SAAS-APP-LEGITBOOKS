<?php

namespace App\Jobs;

use App\Services\MpesaService;
use App\Services\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessMpesaCallbackJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public array $payload, public int $tenantId) {}

    public function handle(MpesaService $mpesaService, TenantContext $tenantContext): void
    {
        $tenant = \App\Models\Tenant::find($this->tenantId);
        if (! $tenant) {
            return;
        }

        // STK callbacks are processed synchronously in Payments\MpesaController and then this job is enqueued for audit.
        // Do not double-process STK; only process C2B-style callbacks here.
        if (isset($this->payload['Body']['stkCallback'])) {
            Log::info('ProcessMpesaCallbackJob: STK callback already processed in controller', [
                'tenant_id' => $this->tenantId,
            ]);

            return;
        }

        $tenantContext->setTenant($tenant);
        $mpesaService->processCallback($this->payload);
    }
}
