<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\Admin;
use App\Models\Tenant;
use App\Models\Invoice;
use App\Models\Contact;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class ExportInvoicesTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;
    protected Tenant $tenant;
    protected Contact $contact;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'owner', 'guard_name' => 'admin']);

        $this->admin = Admin::factory()->create([
            'role' => 'owner',
            'is_active' => true,
        ]);
        $this->admin->assignRole('owner');

        $this->tenant = Tenant::factory()->create();
        $this->contact = Contact::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    public function test_csv_export_respects_filters_and_returns_file(): void
    {
        $invoice1 = Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'contact_id' => $this->contact->id,
            'status' => 'paid',
            'invoice_number' => 'INV-001',
        ]);

        $invoice2 = Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'contact_id' => $this->contact->id,
            'status' => 'sent',
            'invoice_number' => 'INV-002',
        ]);

        $subscription = Subscription::factory()->create([
            'tenant_id' => $this->tenant->id,
            'plan' => 'plan_starter',
        ]);

        $payment = Payment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'subscription_id' => $subscription->id,
            'payment_method' => 'mpesa',
            'mpesa_receipt' => 'TEST123',
            'transaction_status' => 'completed',
        ]);

        PaymentAllocation::factory()->create([
            'invoice_id' => $invoice1->id,
            'payment_id' => $payment->id,
            'amount' => $invoice1->total,
        ]);

        // Export with paid filter
        $response = $this->actingAs($this->admin, 'admin')
            ->get("/admin/tenants/{$this->tenant->id}/invoices/export?status=paid");

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->getContent();
        $this->assertStringContainsString('Invoice Number', $content);
        $this->assertStringContainsString('INV-001', $content);
        $this->assertStringNotContainsString('INV-002', $content);
        $this->assertStringContainsString('TEST123', $content); // Transaction code
    }

    public function test_csv_export_includes_payment_narration_fields(): void
    {
        $invoice = Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'contact_id' => $this->contact->id,
            'status' => 'paid',
            'invoice_number' => 'INV-001',
        ]);

        $subscription = Subscription::factory()->create([
            'tenant_id' => $this->tenant->id,
            'plan' => 'plan_business',
        ]);

        $payment = Payment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'subscription_id' => $subscription->id,
            'amount' => 5000.00,
            'payment_method' => 'mpesa',
            'mpesa_receipt' => 'RECEIPT123',
            'transaction_status' => 'completed',
            'payment_date' => now(),
        ]);

        PaymentAllocation::factory()->create([
            'invoice_id' => $invoice->id,
            'payment_id' => $payment->id,
            'amount' => $invoice->total,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get("/admin/tenants/{$this->tenant->id}/invoices/export?status=paid");

        $content = $response->getContent();
        $lines = str_getcsv($content, "\n");
        
        // Check headers
        $headers = str_getcsv($lines[0]);
        $this->assertContains('Payment Amount', $headers);
        $this->assertContains('Plan', $headers);
        $this->assertContains('Transaction Code', $headers);
        $this->assertContains('Payment Method', $headers);
        $this->assertContains('Payment Date', $headers);

        // Check data row
        if (isset($lines[1])) {
            $data = str_getcsv($lines[1]);
            $this->assertStringContainsString('5000.00', $data[8] ?? ''); // Payment Amount
            $this->assertStringContainsString('Business', $data[9] ?? ''); // Plan
            $this->assertStringContainsString('RECEIPT123', $data[10] ?? ''); // Transaction Code
        }
    }
}

