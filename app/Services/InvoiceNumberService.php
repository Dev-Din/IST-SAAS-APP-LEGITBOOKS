<?php

namespace App\Services;

use App\Models\InvoiceCounter;
use App\Services\TenantContext;
use Illuminate\Support\Facades\DB;

class InvoiceNumberService
{
    public function __construct(
        protected TenantContext $tenantContext
    ) {}

    public function generateNextNumber(): string
    {
        $tenant = $this->tenantContext->getTenant();
        
        if (!$tenant) {
            throw new \Exception('Tenant context not set');
        }

        return DB::transaction(function () use ($tenant) {
            $counter = InvoiceCounter::lockForUpdate()
                ->firstOrCreate(
                    ['tenant_id' => $tenant->id],
                    ['last_number' => 0]
                );

            $counter->increment('last_number');
            $counter->refresh();

            return sprintf('INV-%04d', $counter->last_number);
        });
    }
}

