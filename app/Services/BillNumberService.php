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
     * Format: BILL-{YEAR}-{SEQUENCE} (e.g., BILL-2025-0001)
     * 
     * @param int $tenantId The tenant ID
     * @return string The generated bill number
     * @throws RuntimeException If the bill number cannot be generated
     */
    public function generate(int $tenantId): string
    {
        $year = now()->year;

        try {
            return DB::transaction(function () use ($tenantId, $year) {
                // Use lockForUpdate to prevent concurrent access
                $counter = BillCounter::where('tenant_id', $tenantId)
                    ->where('year', $year)
                    ->lockForUpdate()
                    ->first();

                if ($counter) {
                    // Increment existing counter
                    $counter->increment('sequence');
                    $counter->refresh();
                    $sequence = $counter->sequence;
                } else {
                    // Try to create new counter for this tenant-year combination
                    // Handle race condition: if another transaction created it, retry
                    try {
                        $counter = BillCounter::create([
                            'tenant_id' => $tenantId,
                            'year' => $year,
                            'sequence' => 1,
                        ]);
                        $sequence = 1;
                    } catch (QueryException $e) {
                        // If unique constraint violation, another transaction created it
                        // Retry by fetching and incrementing
                        if ($e->getCode() == 23000 || str_contains($e->getMessage(), 'bill_counters_tenant_id_year_unique')) {
                            $counter = BillCounter::where('tenant_id', $tenantId)
                                ->where('year', $year)
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

                // Format: BILL-{YEAR}-{SEQUENCE} with 4-digit zero padding
                return sprintf('BILL-%d-%04d', $year, $sequence);
            });
        } catch (\Exception $e) {
            throw new RuntimeException(
                'Unable to generate bill number: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }
}

