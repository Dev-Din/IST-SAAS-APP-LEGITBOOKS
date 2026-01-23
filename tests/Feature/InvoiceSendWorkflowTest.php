<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Services\InvoiceSendService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InvoiceSendWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_invoice_send_creates_pdf_and_updates_status()
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $contact = Contact::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'test@example.com',
        ]);

        $invoice = Invoice::factory()->create([
            'tenant_id' => $tenant->id,
            'contact_id' => $contact->id,
            'status' => 'draft',
            'total' => 1000.00,
        ]);

        $this->actingAs($user);

        $tenantContext = app(TenantContext::class);
        $tenantContext->setTenant($tenant);

        $sendService = app(InvoiceSendService::class);

        $result = $sendService->sendInvoice($invoice, $user->id);

        $this->assertTrue($result['success']);
        $this->assertEquals('sent', $invoice->fresh()->status);
        $this->assertNotNull($invoice->fresh()->sent_at);
        $this->assertNotNull($invoice->fresh()->pdf_path);
        $this->assertNotNull($invoice->fresh()->payment_token);
    }

    public function test_invoice_send_creates_journal_entry()
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $contact = Contact::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'test@example.com',
        ]);

        $invoice = Invoice::factory()->create([
            'tenant_id' => $tenant->id,
            'contact_id' => $contact->id,
            'status' => 'draft',
            'total' => 1000.00,
            'subtotal' => 900.00,
            'tax_amount' => 100.00,
        ]);

        $this->actingAs($user);

        $tenantContext = app(TenantContext::class);
        $tenantContext->setTenant($tenant);

        $sendService = app(InvoiceSendService::class);
        $sendService->sendInvoice($invoice, $user->id);

        $journalEntry = $invoice->fresh()->journalEntry;
        $this->assertNotNull($journalEntry);
        $this->assertTrue($journalEntry->isBalanced());
    }

    public function test_invoice_send_creates_audit_log()
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $contact = Contact::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'test@example.com',
        ]);

        $invoice = Invoice::factory()->create([
            'tenant_id' => $tenant->id,
            'contact_id' => $contact->id,
            'status' => 'draft',
        ]);

        $this->actingAs($user);

        $tenantContext = app(TenantContext::class);
        $tenantContext->setTenant($tenant);

        $sendService = app(InvoiceSendService::class);
        $sendService->sendInvoice($invoice, $user->id);

        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenant->id,
            'model_type' => Invoice::class,
            'model_id' => $invoice->id,
            'performed_by' => $user->id,
            'action' => 'sent',
        ]);
    }

    public function test_payment_webhook_allocates_to_invoice()
    {
        $tenant = Tenant::factory()->create();
        $contact = Contact::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'test@example.com',
        ]);

        $invoice = Invoice::factory()->create([
            'tenant_id' => $tenant->id,
            'contact_id' => $contact->id,
            'status' => 'sent',
            'total' => 1000.00,
            'payment_token' => 'test-token-123',
        ]);

        $payload = [
            'PhoneNumber' => '254712345678',
            'TransAmount' => 1000.00,
            'TransID' => 'TEST123456',
            'BillRefNumber' => $invoice->invoice_number,
            'TransTime' => now()->format('YmdHis'),
        ];

        $response = $this->postJson('/webhooks/mpesa', $payload);

        $response->assertStatus(200);
        $response->assertJson(['ResultCode' => 0]);

        $this->assertDatabaseHas('payments', [
            'reference' => 'TEST123456',
        ]);

        $payment = Payment::where('reference', 'TEST123456')->first();
        $this->assertNotNull($payment);

        $this->assertDatabaseHas('payment_allocations', [
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
        ]);

        $this->assertEquals('paid', $invoice->fresh()->status);
    }
}
