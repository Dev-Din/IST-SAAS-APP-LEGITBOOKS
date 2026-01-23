<?php

namespace Tests\Unit;

use App\Models\Account;
use App\Models\ChartOfAccount;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Tenant;
use App\Services\PaymentService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PaymentService $service;

    protected Tenant $tenant;

    protected TenantContext $tenantContext;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = $this->createTestTenant();
        $this->tenantContext = app(TenantContext::class);
        $this->tenantContext->setTenant($this->tenant);

        $this->service = new PaymentService($this->tenantContext);

        $this->createRequiredAccounts();
    }

    public function test_process_payment_creates_journal_entry(): void
    {
        $payment = $this->createTestPayment();
        $allocations = [];

        $journalEntry = $this->service->processPayment($payment, $allocations);

        $this->assertInstanceOf(JournalEntry::class, $journalEntry);
        $this->assertTrue($journalEntry->is_posted);
        $this->assertEquals(Payment::class, $journalEntry->reference_type);
        $this->assertEquals($payment->id, $journalEntry->reference_id);
    }

    public function test_process_payment_creates_balanced_journal_entry(): void
    {
        $payment = $this->createTestPayment();
        $allocations = [];

        $journalEntry = $this->service->processPayment($payment, $allocations);

        $this->assertTrue($journalEntry->isBalanced());
        $this->assertEquals($journalEntry->total_debits, $journalEntry->total_credits);
    }

    public function test_process_payment_debits_bank_account(): void
    {
        $payment = $this->createTestPayment();
        $bankAccount = Account::where('name', 'Test Bank')->first();
        $coa = $bankAccount->chartOfAccount;

        $journalEntry = $this->service->processPayment($payment, []);

        $debitLine = JournalLine::where('journal_entry_id', $journalEntry->id)
            ->where('chart_of_account_id', $coa->id)
            ->where('type', 'debit')
            ->first();

        $this->assertNotNull($debitLine);
        $this->assertEquals($payment->amount, $debitLine->amount);
    }

    public function test_process_payment_creates_payment_allocations(): void
    {
        $invoice = $this->createTestInvoice();
        $payment = $this->createTestPayment();

        $allocations = [
            ['invoice_id' => $invoice->id, 'amount' => 500.00],
        ];

        $journalEntry = $this->service->processPayment($payment, $allocations);

        $allocation = PaymentAllocation::where('payment_id', $payment->id)->first();
        $this->assertNotNull($allocation);
        $this->assertEquals($invoice->id, $allocation->invoice_id);
        $this->assertEquals(500.00, $allocation->amount);
    }

    public function test_process_payment_credits_ar_for_allocations(): void
    {
        $invoice = $this->createTestInvoice();
        $payment = $this->createTestPayment();
        $arAccount = ChartOfAccount::where('code', '1200')->first();

        $allocations = [
            ['invoice_id' => $invoice->id, 'amount' => 500.00],
        ];

        $journalEntry = $this->service->processPayment($payment, $allocations);

        $creditLine = JournalLine::where('journal_entry_id', $journalEntry->id)
            ->where('chart_of_account_id', $arAccount->id)
            ->where('type', 'credit')
            ->first();

        $this->assertNotNull($creditLine);
        $this->assertEquals(500.00, $creditLine->amount);
    }

    public function test_process_payment_handles_overpayment(): void
    {
        $invoice = $this->createTestInvoice();
        $payment = $this->createTestPayment(['amount' => 1500.00]);

        $allocations = [
            ['invoice_id' => $invoice->id, 'amount' => 1000.00],
        ];

        $journalEntry = $this->service->processPayment($payment, $allocations);

        // Should credit unapplied amount to liability account
        $unappliedLine = JournalLine::where('journal_entry_id', $journalEntry->id)
            ->where('type', 'credit')
            ->where('amount', 500.00)
            ->first();

        $this->assertNotNull($unappliedLine);
        $this->assertTrue($journalEntry->isBalanced());
    }

    public function test_process_payment_throws_exception_if_ar_account_missing(): void
    {
        ChartOfAccount::where('code', '1200')->delete();
        $payment = $this->createTestPayment();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Accounts Receivable account not found');

        $this->service->processPayment($payment, []);
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
        // Bank Account COA
        $bankCOA = ChartOfAccount::create([
            'tenant_id' => $this->tenant->id,
            'code' => '1300',
            'name' => 'Bank',
            'type' => 'asset',
            'category' => 'current_asset',
            'is_active' => true,
        ]);

        // Bank Account
        Account::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Bank',
            'type' => 'bank',
            'chart_of_account_id' => $bankCOA->id,
            'opening_balance' => 0,
            'is_active' => true,
        ]);

        // Accounts Receivable
        ChartOfAccount::create([
            'tenant_id' => $this->tenant->id,
            'code' => '1200',
            'name' => 'Accounts Receivable',
            'type' => 'asset',
            'category' => 'current_asset',
            'is_active' => true,
        ]);

        // Tax Payable (for overpayment handling)
        ChartOfAccount::create([
            'tenant_id' => $this->tenant->id,
            'code' => '2200',
            'name' => 'Tax Payable',
            'type' => 'liability',
            'category' => 'current_liability',
            'is_active' => true,
        ]);
    }

    protected function createTestPayment(array $attributes = []): Payment
    {
        $account = Account::where('name', 'Test Bank')->first();
        $contact = Contact::firstOrCreate(
            ['tenant_id' => $this->tenant->id, 'email' => 'payment@example.com'],
            ['name' => 'Payment Contact', 'type' => 'customer']
        );

        return Payment::create(array_merge([
            'tenant_id' => $this->tenant->id,
            'payment_number' => 'PAY-'.uniqid(),
            'payment_date' => now(),
            'account_id' => $account->id,
            'contact_id' => $contact->id,
            'amount' => 1000.00,
            'payment_method' => 'cash',
            'transaction_status' => 'pending',
        ], $attributes));
    }

    protected function createTestInvoice(): Invoice
    {
        $contact = Contact::firstOrCreate(
            ['tenant_id' => $this->tenant->id, 'email' => 'invoice@example.com'],
            ['name' => 'Invoice Contact', 'type' => 'customer']
        );

        return Invoice::create([
            'tenant_id' => $this->tenant->id,
            'invoice_number' => 'INV-2025-0001',
            'contact_id' => $contact->id,
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'status' => 'draft',
            'subtotal' => 1000.00,
            'tax_amount' => 0.00,
            'total' => 1000.00,
        ]);
    }
}
