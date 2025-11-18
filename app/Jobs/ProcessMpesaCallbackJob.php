<?php

namespace App\Jobs;

use App\Services\MpesaService;
use App\Services\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessMpesaCallbackJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public array $payload, public int $tenantId) {}

    public function handle(MpesaService $mpesaService, TenantContext $tenantContext): void
    {
        $tenant = \App\Models\Tenant::find($this->tenantId);
        if (!$tenant) {
            return;
        }

        $tenantContext->setTenant($tenant);
        $mpesaService->processCallback($this->payload);
    }
}
