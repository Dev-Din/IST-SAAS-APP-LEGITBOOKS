<?php

namespace App\Services;

use App\Models\InvoiceCounter;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InvoiceNumberService
{
    /**
     * Generate a unique invoice number for the given tenant.
     *
     * Format: INV-{SEQUENCE} (e.g., INV-001)
     *
     * @param  int  $tenantId  The tenant ID
     * @return string The generated invoice number
     *
     * @throws RuntimeException If the invoice number cannot be generated
     */
    public function generate(int $tenantId): string
    {
        try {
            return DB::transaction(function () use ($tenantId) {
                // Use lockForUpdate to prevent concurrent access
                $counter = InvoiceCounter::where('tenant_id', $tenantId)
                    ->lockForUpdate()
                    ->first();

                if ($counter) {
                    // Increment existing counter
                    $counter->increment('sequence');
                    $counter->refresh();
                    $sequence = $counter->sequence;
                } else {
                    // Try to create new counter for this tenant
                    // Handle race condition: if another transaction created it, retry
                    try {
                        $counter = InvoiceCounter::create([
                            'tenant_id' => $tenantId,
                            'sequence' => 1,
                        ]);
                        $sequence = 1;
                    } catch (QueryException $e) {
                        // If unique constraint violation, another transaction created it
                        // Retry by fetching and incrementing
                        if ($e->getCode() == 23000 || str_contains($e->getMessage(), 'invoice_counters_tenant_id_unique')) {
                            $counter = InvoiceCounter::where('tenant_id', $tenantId)
                                ->lockForUpdate()
                                ->firstOrFail();
                            $counter->increment('sequence');
                            $counter->refresh();
                            $sequence = $counter->sequence;
                        } else {
                            throw $e;
                        }
                    }
                }

                // Format: INV-{SEQUENCE} with 3-digit zero padding
                return sprintf('INV-%03d', $sequence);
            });
        } catch (\Exception $e) {
            throw new RuntimeException(
                'Unable to generate invoice number: '.$e->getMessage(),
                0,
                $e
            );
        }
    }
}
