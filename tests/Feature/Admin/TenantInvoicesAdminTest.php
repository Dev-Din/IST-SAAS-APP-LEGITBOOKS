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
use Carbon\Carbon;

class TenantInvoicesAdminTest extends TestCase
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

    public function test_invoice_filters_and_paid_narration_included(): void
    {
        // Create invoices with different statuses
        $paidInvoice = Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'contact_id' => $this->contact->id,
            'status' => 'paid',
            'invoice_date' => now()->subDays(10),
            'due_date' => now()->subDays(5),
            'total' => 1000.00,
        ]);

        $dueInvoice = Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'contact_id' => $this->contact->id,
            'status' => 'sent',
            'invoice_date' => now()->subDays(5),
            'due_date' => now()->addDays(5),
            'total' => 2000.00,
        ]);

        $overdueInvoice = Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'contact_id' => $this->contact->id,
            'status' => 'sent',
            'invoice_date' => now()->subDays(20),
            'due_date' => now()->subDays(5),
            'total' => 3000.00,
        ]);

        // Create subscription and payment for paid invoice
        $subscription = Subscription::factory()->create([
            'tenant_id' => $this->tenant->id,
            'plan' => 'plan_starter',
        ]);

        $payment = Payment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'subscription_id' => $subscription->id,
            'amount' => 1000.00,
            'payment_method' => 'mpesa',
            'mpesa_receipt' => 'ABC123',
            'transaction_status' => 'completed',
            'payment_date' => now()->subDays(8),
        ]);

        PaymentAllocation::factory()->create([
            'invoice_id' => $paidInvoice->id,
            'payment_id' => $payment->id,
            'amount' => 1000.00,
        ]);

        // Test all invoices
        $response = $this->actingAs($this->admin, 'admin')
            ->getJson("/admin/tenants/{$this->tenant->id}/invoices?status=all");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'invoices' => [['id', 'invoice_number', 'client_name', 'status', 'payment_narration']],
                'status_counts',
            ]);

        $data = $response->json();
        $this->assertCount(3, $data['invoices']);

        // Test paid filter
        $response = $this->actingAs($this->admin, 'admin')
            ->getJson("/admin/tenants/{$this->tenant->id}/invoices?status=paid");

        $data = $response->json();
        $this->assertCount(1, $data['invoices']);
        $this->assertEquals('paid', $data['invoices'][0]['status']);
        $this->assertNotNull($data['invoices'][0]['payment_narration']['payment_amount']);

        // Test overdue filter
        $response = $this->actingAs($this->admin, 'admin')
            ->getJson("/admin/tenants/{$this->tenant->id}/invoices?status=overdue");

        $data = $response->json();
        $this->assertCount(1, $data['invoices']);
        $this->assertTrue($data['invoices'][0]['is_overdue']);

        // Test invoice detail with full narration
        $response = $this->actingAs($this->admin, 'admin')
            ->getJson("/admin/tenants/{$this->tenant->id}/invoices/{$paidInvoice->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'invoice' => ['payment_narration' => ['narration']],
            ]);

        $data = $response->json();
        $this->assertNotNull($data['invoice']['payment_narration']['narration']);
        $this->assertStringContainsString('Payment Amount', $data['invoice']['payment_narration']['narration']);
        $this->assertStringContainsString('Transaction Code', $data['invoice']['payment_narration']['narration']);
    }

    public function test_invoice_date_range_filter(): void
    {
        $invoice1 = Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'contact_id' => $this->contact->id,
            'invoice_date' => now()->subDays(30),
        ]);

        $invoice2 = Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'contact_id' => $this->contact->id,
            'invoice_date' => now()->subDays(5),
        ]);

        $dateFrom = now()->subDays(10)->format('Y-m-d');
        $dateTo = now()->format('Y-m-d');

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson("/admin/tenants/{$this->tenant->id}/invoices?date_from={$dateFrom}&date_to={$dateTo}");

        $data = $response->json();
        $this->assertCount(1, $data['invoices']);
        $this->assertEquals($invoice2->id, $data['invoices'][0]['id']);
    }
}

