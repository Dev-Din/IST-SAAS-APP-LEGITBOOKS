<?php

namespace App\Services;

use App\Models\BillCounter;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BillNumberService
{
    /**
     * Generate a unique bill number for the given tenant.
     *
     * Format: Bill-{SEQUENCE} (e.g., Bill-001)
     *
     * @param  int  $tenantId  The tenant ID
     * @return string The generated bill number
     *
     * @throws RuntimeException If the bill number cannot be generated
     */
    public function generate(int $tenantId): string
    {
        try {
            return DB::transaction(function () use ($tenantId) {
                // Use lockForUpdate to prevent concurrent access
                $counter = BillCounter::where('tenant_id', $tenantId)
                    ->lockForUpdate()
                    ->first();

                if ($counter) {
                    // Increment existing counter
                    $counter->increment('counter');
                    $counter->refresh();
                    $sequence = $counter->counter;
                } else {
                    // Try to create new counter for this tenant
                    // Handle race condition: if another transaction created it, retry
                    try {
                        $counter = BillCounter::create([
                            'tenant_id' => $tenantId,
                            'counter' => 1,
                            'prefix' => 'Bill',
                            'format' => 'Bill-{COUNTER}',
                        ]);
                        $sequence = 1;
                    } catch (QueryException $e) {
                        // If unique constraint violation, another transaction created it
                        // Retry by fetching and incrementing
                        if ($e->getCode() == 23000 || str_contains($e->getMessage(), 'bill_counters_tenant_id_unique')) {
                            $counter = BillCounter::where('tenant_id', $tenantId)
                                ->lockForUpdate()
                                ->firstOrFail();
                            $counter->increment('counter');
                            $counter->refresh();
                            $sequence = $counter->counter;
                        } else {
                            throw $e;
                        }
                    }
                }

                // Format: Bill-{SEQUENCE} with 3-digit zero padding
                return sprintf('Bill-%03d', $sequence);
            });
        } catch (\Exception $e) {
            throw new RuntimeException(
                'Unable to generate bill number: '.$e->getMessage(),
                0,
                $e
            );
        }
    }
}
