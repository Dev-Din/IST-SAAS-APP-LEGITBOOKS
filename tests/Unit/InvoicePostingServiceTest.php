<?php

namespace Tests\Unit;

use App\Models\ChartOfAccount;
use App\Models\Invoice;
use App\Models\InvoiceCounter;
use App\Models\InvoiceLineItem;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Tenant;
use App\Services\InvoicePostingService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoicePostingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected InvoicePostingService $service;
    protected Tenant $tenant;
    protected TenantContext $tenantContext;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tenant = $this->createTestTenant();
        $this->tenantContext = app(TenantContext::class);
        $this->tenantContext->setTenant($this->tenant);
        
        $this->service = new InvoicePostingService($this->tenantContext);
        
        // Create required chart of accounts
        $this->createRequiredAccounts();
    }

    public function test_post_invoice_creates_journal_entry(): void
    {
        $invoice = $this->createTestInvoice();
        
        $journalEntry = $this->service->postInvoice($invoice);
        
        $this->assertInstanceOf(JournalEntry::class, $journalEntry);
        $this->assertTrue($journalEntry->is_posted);
        $this->assertEquals(Invoice::class, $journalEntry->reference_type);
        $this->assertEquals($invoice->id, $journalEntry->reference_id);
    }

    public function test_post_invoice_creates_balanced_journal_entry(): void
    {
        $invoice = $this->createTestInvoice();
        
        $journalEntry = $this->service->postInvoice($invoice);
        
        $this->assertTrue($journalEntry->isBalanced());
        $this->assertEquals($journalEntry->total_debits, $journalEntry->total_credits);
    }

    public function test_post_invoice_debits_accounts_receivable(): void
    {
        $invoice = $this->createTestInvoice();
        $arAccount = ChartOfAccount::where('code', '1200')->first();
        
        $journalEntry = $this->service->postInvoice($invoice);
        
        $arLine = JournalLine::where('journal_entry_id', $journalEntry->id)
            ->where('chart_of_account_id', $arAccount->id)
            ->where('type', 'debit')
            ->first();
        
        $this->assertNotNull($arLine);
        $this->assertEquals($invoice->total, $arLine->amount);
    }

    public function test_post_invoice_credits_revenue_accounts(): void
    {
        $invoice = $this->createTestInvoice();
        $salesAccount = ChartOfAccount::where('code', '4100')->first();
        
        $journalEntry = $this->service->postInvoice($invoice);
        
        $revenueLine = JournalLine::where('journal_entry_id', $journalEntry->id)
            ->where('chart_of_account_id', $salesAccount->id)
            ->where('type', 'credit')
            ->first();
        
        $this->assertNotNull($revenueLine);
        // Should credit the subtotal (without tax)
        $this->assertEquals($invoice->subtotal, $revenueLine->amount);
    }

    public function test_post_invoice_credits_tax_liability_when_tax_applicable(): void
    {
        $invoice = $this->createTestInvoice(['tax_amount' => 160.00]);
        $taxAccount = ChartOfAccount::where('code', '2200')->first();
        
        $journalEntry = $this->service->postInvoice($invoice);
        
        $taxLine = JournalLine::where('journal_entry_id', $journalEntry->id)
            ->where('chart_of_account_id', $taxAccount->id)
            ->where('type', 'credit')
            ->first();
        
        $this->assertNotNull($taxLine);
        $this->assertEquals(160.00, $taxLine->amount);
    }

    public function test_post_invoice_throws_exception_if_ar_account_missing(): void
    {
        ChartOfAccount::where('code', '1200')->delete();
        $invoice = $this->createTestInvoice();
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Accounts Receivable account not found');
        
        $this->service->postInvoice($invoice);
    }

    public function test_post_invoice_uses_line_item_sales_account(): void
    {
        $customSalesAccount = ChartOfAccount::create([
            'tenant_id' => $this->tenant->id,
            'code' => '4200',
            'name' => 'Custom Sales',
            'type' => 'revenue',
            'category' => 'revenue',
            'is_active' => true,
        ]);
        
        $invoice = $this->createTestInvoice();
        $invoice->lineItems()->update(['sales_account_id' => $customSalesAccount->id]);
        $invoice->refresh();
        
        $journalEntry = $this->service->postInvoice($invoice);
        
        $customLine = JournalLine::where('journal_entry_id', $journalEntry->id)
            ->where('chart_of_account_id', $customSalesAccount->id)
            ->first();
        
        $this->assertNotNull($customLine);
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

    protected function createRequiredAccounts(): void
    {
        // Accounts Receivable (1200)
        ChartOfAccount::create([
            'tenant_id' => $this->tenant->id,
            'code' => '1200',
            'name' => 'Accounts Receivable',
            'type' => 'asset',
            'category' => 'current_asset',
            'is_active' => true,
        ]);

        // Sales Revenue (4100)
        ChartOfAccount::create([
            'tenant_id' => $this->tenant->id,
            'code' => '4100',
            'name' => 'Sales Revenue',
            'type' => 'revenue',
            'category' => 'revenue',
            'is_active' => true,
        ]);

        // Tax Liability (2200)
        ChartOfAccount::create([
            'tenant_id' => $this->tenant->id,
            'code' => '2200',
            'name' => 'Tax Payable',
            'type' => 'liability',
            'category' => 'current_liability',
            'is_active' => true,
        ]);
    }

    protected function createTestInvoice(array $attributes = []): Invoice
    {
        $contact = \App\Models\Contact::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Contact',
            'email' => 'contact@example.com',
            'type' => 'customer',
        ]);

        $invoice = Invoice::create(array_merge([
            'tenant_id' => $this->tenant->id,
            'invoice_number' => 'INV-2025-0001',
            'contact_id' => $contact->id,
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'status' => 'draft',
            'subtotal' => 1000.00,
            'tax_amount' => $attributes['tax_amount'] ?? 0.00,
            'total' => 1000.00 + ($attributes['tax_amount'] ?? 0.00),
        ], $attributes));

        InvoiceLineItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Test Item',
            'quantity' => 1,
            'unit_price' => 1000.00,
            'tax_rate' => 0,
            'line_total' => 1000.00,
            'sales_account_id' => ChartOfAccount::where('code', '4100')->first()->id,
        ]);

        return $invoice->fresh(['lineItems']);
    }
}
