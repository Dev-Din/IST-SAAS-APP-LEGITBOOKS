<?php

namespace Tests\Feature\Integration;

use App\Models\Account;
use App\Models\ChartOfAccount;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\InvoiceCounter;
use App\Models\InvoiceLineItem;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Tenant;
use App\Models\User;
use App\Services\InvoicePostingService;
use App\Services\PaymentService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InvoicePaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $user;
    protected ChartOfAccount $arAccount;
    protected ChartOfAccount $salesAccount;
    protected Account $bankAccount;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tenant = Tenant::create([
            'name' => 'Test Tenant',
            'email' => 'test@example.com',
            'tenant_hash' => Tenant::generateTenantHash(),
            'status' => 'active',
            'settings' => [],
        ]);

        InvoiceCounter::create([
            'tenant_id' => $this->tenant->id,
            'year' => now()->year,
            'sequence' => 0,
        ]);

        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
            'is_owner' => true,
        ]);

        // Create required accounts
        $this->arAccount = ChartOfAccount::create([
            'tenant_id' => $this->tenant->id,
            'code' => '1200',
            'name' => 'Accounts Receivable',
            'type' => 'asset',
            'category' => 'current_asset',
            'is_active' => true,
        ]);

        $this->salesAccount = ChartOfAccount::create([
            'tenant_id' => $this->tenant->id,
            'code' => '4100',
            'name' => 'Sales Revenue',
            'type' => 'revenue',
            'category' => 'revenue',
            'is_active' => true,
        ]);

        $bankCOA = ChartOfAccount::create([
            'tenant_id' => $this->tenant->id,
            'code' => '1300',
            'name' => 'Bank',
            'type' => 'asset',
            'category' => 'current_asset',
            'is_active' => true,
        ]);

        $this->bankAccount = Account::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Bank',
            'type' => 'bank',
            'chart_of_account_id' => $bankCOA->id,
            'is_active' => true,
        ]);

        app(TenantContext::class)->setTenant($this->tenant);
    }

    public function test_complete_invoice_to_payment_flow(): void
    {
        // 1. Create contact
        $contact = Contact::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
            'type' => 'customer',
        ]);

        // 2. Create invoice
        $invoice = Invoice::create([
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

        InvoiceLineItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Test Item',
            'quantity' => 1,
            'unit_price' => 1000.00,
            'tax_rate' => 0,
            'line_total' => 1000.00,
            'sales_account_id' => $this->salesAccount->id,
        ]);

        // 3. Post invoice (create journal entry)
        $postingService = app(InvoicePostingService::class);
        $journalEntry = $postingService->postInvoice($invoice);

        $this->assertNotNull($journalEntry);
        $this->assertTrue($journalEntry->isBalanced());
        $this->assertEquals(1000.00, $journalEntry->total_debits);
        $this->assertEquals(1000.00, $journalEntry->total_credits);

        // 4. Create payment
        $payment = Payment::create([
            'tenant_id' => $this->tenant->id,
            'payment_number' => 'PAY-001',
            'payment_date' => now(),
            'account_id' => $this->bankAccount->id,
            'contact_id' => $contact->id,
            'amount' => 1000.00,
            'payment_method' => 'cash',
            'transaction_status' => 'completed',
        ]);

        // 5. Allocate payment to invoice
        $paymentService = app(PaymentService::class);
        $paymentJournal = $paymentService->processPayment($payment, [
            ['invoice_id' => $invoice->id, 'amount' => 1000.00],
        ]);

        $this->assertNotNull($paymentJournal);
        $this->assertTrue($paymentJournal->isBalanced());

        // 6. Verify payment allocation
        $allocation = PaymentAllocation::where('payment_id', $payment->id)
            ->where('invoice_id', $invoice->id)
            ->first();
        $this->assertNotNull($allocation);
        $this->assertEquals(1000.00, $allocation->amount);

        // 7. Verify invoice status updated
        $invoice->refresh();
        $outstanding = $invoice->getOutstandingAmount();
        $this->assertEquals(0.00, $outstanding);
    }
}
