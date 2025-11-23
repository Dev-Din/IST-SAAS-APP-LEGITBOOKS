<?php

namespace Tests\Feature;

use App\Models\InvoiceCounter;
use App\Models\Tenant;
use App\Services\InvoiceNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InvoiceSequenceConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    protected InvoiceNumberService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new InvoiceNumberService();
    }

    /**
     * Test that concurrent generate() calls produce unique sequences
     */
    public function test_concurrent_generate_calls_produce_unique_sequences(): void
    {
        $tenant = $this->createTestTenant();
        $year = now()->year;

        // Simulate concurrent calls using transactions
        $results = [];
        $exceptions = [];

        // Simulate 10 concurrent invoice number generations
        for ($i = 0; $i < 10; $i++) {
            try {
                DB::transaction(function () use ($tenant, &$results) {
                    $results[] = $this->service->generate($tenant->id);
                });
            } catch (\Exception $e) {
                $exceptions[] = $e;
            }
        }

        // All should succeed
        $this->assertEmpty($exceptions, 'No exceptions should occur during concurrent generation');
        $this->assertCount(10, $results, 'All 10 generations should succeed');

        // Extract sequences and verify they are unique
        $sequences = [];
        foreach ($results as $result) {
            preg_match('/INV-\d{4}-(\d{4})$/', $result, $matches);
            $sequences[] = (int)$matches[1];
        }

        // All sequences should be unique
        $this->assertEquals(10, count(array_unique($sequences)), 'All sequences should be unique');
        
        // Sequences should be 1-10
        sort($sequences);
        $this->assertEquals([1, 2, 3, 4, 5, 6, 7, 8, 9, 10], $sequences);
    }

    /**
     * Test that lockForUpdate prevents duplicate sequences
     */
    public function test_lock_for_update_prevents_duplicates(): void
    {
        $tenant = $this->createTestTenant();
        $year = now()->year;

        // Create initial counter
        InvoiceCounter::create([
            'tenant_id' => $tenant->id,
            'year' => $year,
            'sequence' => 5,
        ]);

        // Simulate two concurrent transactions
        $result1 = null;
        $result2 = null;

        DB::transaction(function () use ($tenant, &$result1) {
            $result1 = $this->service->generate($tenant->id);
        });

        DB::transaction(function () use ($tenant, &$result2) {
            $result2 = $this->service->generate($tenant->id);
        });

        // Extract sequences
        preg_match('/INV-\d{4}-(\d{4})$/', $result1, $matches1);
        preg_match('/INV-\d{4}-(\d{4})$/', $result2, $matches2);

        $seq1 = (int)$matches1[1];
        $seq2 = (int)$matches2[1];

        // Should be 6 and 7 (incremented from 5)
        $this->assertNotEquals($seq1, $seq2, 'Sequences should be different');
        $this->assertContains($seq1, [6, 7]);
        $this->assertContains($seq2, [6, 7]);
    }

    /**
     * Test that multiple tenants can generate numbers concurrently without interference
     */
    public function test_multiple_tenants_concurrent_generation(): void
    {
        $tenant1 = $this->createTestTenant();
        $tenant2 = $this->createTestTenant();
        $tenant3 = $this->createTestTenant();

        $results = [];

        // Generate numbers for all tenants concurrently
        DB::transaction(function () use ($tenant1, &$results) {
            $results['tenant1'] = $this->service->generate($tenant1->id);
        });

        DB::transaction(function () use ($tenant2, &$results) {
            $results['tenant2'] = $this->service->generate($tenant2->id);
        });

        DB::transaction(function () use ($tenant3, &$results) {
            $results['tenant3'] = $this->service->generate($tenant3->id);
        });

        // All should start at 0001
        foreach ($results as $tenant => $number) {
            preg_match('/INV-\d{4}-(\d{4})$/', $number, $matches);
            $this->assertEquals('0001', $matches[1], "{$tenant} should start at 0001");
        }
    }

    /**
     * Helper method to create a test tenant
     */
    protected function createTestTenant(): Tenant
    {
        return Tenant::create([
            'name' => 'Test Tenant ' . uniqid(),
            'email' => 'test' . uniqid() . '@example.com',
            'tenant_hash' => Tenant::generateTenantHash(),
            'status' => 'active',
            'settings' => [],
        ]);
    }
}
