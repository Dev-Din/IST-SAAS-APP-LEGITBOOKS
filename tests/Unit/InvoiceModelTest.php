<?php

namespace Tests\Unit;

use App\Models\Contact;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\PaymentAllocation;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceModelTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = $this->createTestTenant();
    }

    public function test_invoice_has_tenant_relationship(): void
    {
        $invoice = $this->createTestInvoice();

        $this->assertInstanceOf(Tenant::class, $invoice->tenant);
        $this->assertEquals($this->tenant->id, $invoice->tenant->id);
    }

    public function test_invoice_has_contact_relationship(): void
    {
        $contact = $this->createTestContact();
        $invoice = $this->createTestInvoice(['contact_id' => $contact->id]);

        $this->assertInstanceOf(Contact::class, $invoice->contact);
        $this->assertEquals($contact->id, $invoice->contact->id);
    }

    public function test_invoice_has_line_items_relationship(): void
    {
        $invoice = $this->createTestInvoice();

        InvoiceLineItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Item 1',
            'quantity' => 1,
            'unit_price' => 100.00,
            'tax_rate' => 0,
            'line_total' => 100.00,
        ]);

        $this->assertCount(1, $invoice->lineItems);
    }

    public function test_invoice_is_paid_returns_true_when_status_is_paid(): void
    {
        $invoice = $this->createTestInvoice(['status' => 'paid']);

        $this->assertTrue($invoice->isPaid());
    }

    public function test_invoice_is_paid_returns_false_when_status_is_not_paid(): void
    {
        $invoice = $this->createTestInvoice(['status' => 'draft']);

        $this->assertFalse($invoice->isPaid());
    }

    public function test_get_outstanding_amount_returns_correct_value(): void
    {
        $invoice = $this->createTestInvoice(['total' => 1000.00]);

        PaymentAllocation::create([
            'payment_id' => 1,
            'invoice_id' => $invoice->id,
            'amount' => 300.00,
        ]);

        $outstanding = $invoice->getOutstandingAmount();
        $this->assertEquals(700.00, $outstanding);
    }

    public function test_get_outstanding_amount_returns_zero_when_fully_paid(): void
    {
        $invoice = $this->createTestInvoice(['total' => 1000.00]);

        PaymentAllocation::create([
            'payment_id' => 1,
            'invoice_id' => $invoice->id,
            'amount' => 1000.00,
        ]);

        $outstanding = $invoice->getOutstandingAmount();
        $this->assertEquals(0.00, $outstanding);
    }

    public function test_get_outstanding_amount_never_returns_negative(): void
    {
        $invoice = $this->createTestInvoice(['total' => 1000.00]);

        PaymentAllocation::create([
            'payment_id' => 1,
            'invoice_id' => $invoice->id,
            'amount' => 1500.00, // Overpayment
        ]);

        $outstanding = $invoice->getOutstandingAmount();
        $this->assertEquals(0.00, $outstanding);
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

    protected function createTestContact(): Contact
    {
        return Contact::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Contact',
            'email' => 'contact@example.com',
            'type' => 'customer',
        ]);
    }

    protected function createTestInvoice(array $attributes = []): Invoice
    {
        $contact = $this->createTestContact();

        return Invoice::create(array_merge([
            'tenant_id' => $this->tenant->id,
            'invoice_number' => 'INV-2025-0001',
            'contact_id' => $contact->id,
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'status' => 'draft',
            'subtotal' => 1000.00,
            'tax_amount' => 0.00,
            'total' => 1000.00,
        ], $attributes));
    }
}
