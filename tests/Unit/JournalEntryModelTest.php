<?php

namespace Tests\Unit;

use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JournalEntryModelTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = $this->createTestTenant();
    }

    public function test_is_balanced_returns_true_when_debits_equal_credits(): void
    {
        $entry = $this->createTestJournalEntry();

        JournalLine::create([
            'journal_entry_id' => $entry->id,
            'chart_of_account_id' => 1,
            'type' => 'debit',
            'amount' => 1000.00,
        ]);

        JournalLine::create([
            'journal_entry_id' => $entry->id,
            'chart_of_account_id' => 2,
            'type' => 'credit',
            'amount' => 1000.00,
        ]);

        $entry->calculateTotals();
        $entry->save();

        $this->assertTrue($entry->isBalanced());
    }

    public function test_is_balanced_returns_false_when_debits_not_equal_credits(): void
    {
        $entry = $this->createTestJournalEntry();

        JournalLine::create([
            'journal_entry_id' => $entry->id,
            'chart_of_account_id' => 1,
            'type' => 'debit',
            'amount' => 1000.00,
        ]);

        JournalLine::create([
            'journal_entry_id' => $entry->id,
            'chart_of_account_id' => 2,
            'type' => 'credit',
            'amount' => 500.00,
        ]);

        $entry->calculateTotals();
        $entry->save();

        $this->assertFalse($entry->isBalanced());
    }

    public function test_calculate_totals_sums_debits_and_credits(): void
    {
        $entry = $this->createTestJournalEntry();
        $coa1 = $this->createTestCOA();
        $coa2 = $this->createTestCOA();

        JournalLine::create([
            'journal_entry_id' => $entry->id,
            'chart_of_account_id' => $coa1->id,
            'type' => 'debit',
            'amount' => 1000.00,
        ]);

        JournalLine::create([
            'journal_entry_id' => $entry->id,
            'chart_of_account_id' => $coa2->id,
            'type' => 'credit',
            'amount' => 1000.00,
        ]);

        $entry->calculateTotals();
        $entry->save();

        $this->assertEquals(1000.00, $entry->total_debits);
        $this->assertEquals(1000.00, $entry->total_credits);
    }

    public function test_journal_entry_has_lines_relationship(): void
    {
        $entry = $this->createTestJournalEntry();
        $coa = $this->createTestCOA();

        JournalLine::create([
            'journal_entry_id' => $entry->id,
            'chart_of_account_id' => $coa->id,
            'type' => 'debit',
            'amount' => 1000.00,
        ]);

        $this->assertCount(1, $entry->lines);
    }

    protected function createTestTenant(): Tenant
    {
        return Tenant::create([
            'name' => 'Test Tenant',
            'email' => 'test@example.com',
            'tenant_hash' => Tenant::generateTenantHash(),
            'status' => 'active',
            'settings' => [],
        ]);
    }

    protected function createTestCOA(): ChartOfAccount
    {
        return ChartOfAccount::create([
            'tenant_id' => $this->tenant->id,
            'code' => 'TEST'.uniqid(),
            'name' => 'Test Account',
            'type' => 'asset',
            'category' => 'current_asset',
            'is_active' => true,
        ]);
    }

    protected function createTestJournalEntry(): JournalEntry
    {
        return JournalEntry::create([
            'tenant_id' => $this->tenant->id,
            'entry_number' => 'JE-20250118-0001',
            'entry_date' => now(),
            'description' => 'Test Entry',
            'total_debits' => 0,
            'total_credits' => 0,
            'is_posted' => false,
        ]);
    }
}
