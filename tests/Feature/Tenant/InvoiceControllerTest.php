<?php

namespace Tests\Feature\Tenant;

use App\Models\Account;
use App\Models\ChartOfAccount;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\InvoiceCounter;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Mail\PHPMailerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InvoiceControllerTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected User $user;

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
            'permissions' => ['manage_invoices', 'view_invoices'],
        ]);

        // Set tenant context
        app(\App\Services\TenantContext::class)->setTenant($this->tenant);
    }

    public function test_index_displays_invoices(): void
    {
        $contact = Contact::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Contact',
            'email' => 'contact@example.com',
            'type' => 'customer',
        ]);

        Invoice::create([
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

        $response = $this->actingAs($this->user, 'web')
            ->get(route('tenant.invoices.index'));

        $response->assertStatus(200);
        $response->assertViewIs('tenant.invoices.index');
        $response->assertViewHas('invoices');
    }

    public function test_create_displays_form(): void
    {
        Contact::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Contact',
            'email' => 'contact@example.com',
            'type' => 'customer',
        ]);

        $response = $this->actingAs($this->user, 'web')
            ->get(route('tenant.invoices.create'));

        $response->assertStatus(200);
        $response->assertViewIs('tenant.invoices.create');
    }

    public function test_store_creates_invoice(): void
    {
        $contact = Contact::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Contact',
            'email' => 'contact@example.com',
            'type' => 'customer',
        ]);

        $response = $this->actingAs($this->user, 'web')
            ->post(route('tenant.invoices.store'), [
                'contact_id' => $contact->id,
                'invoice_date' => now()->format('Y-m-d'),
                'due_date' => now()->addDays(30)->format('Y-m-d'),
                'line_items' => [
                    [
                        'description' => 'Test Item',
                        'quantity' => 1,
                        'unit_price' => 1000.00,
                        'tax_rate' => 0,
                    ],
                ],
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('invoices', [
            'tenant_id' => $this->tenant->id,
            'contact_id' => $contact->id,
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user, 'web')
            ->post(route('tenant.invoices.store'), []);

        $response->assertSessionHasErrors(['contact_id', 'invoice_date', 'line_items']);
    }

    /**
     * Send receipt sends email with receipt PDF attached.
     */
    public function test_send_receipt_sends_email_with_receipt_pdf_attachment(): void
    {
        Storage::fake('local');

        $contact = Contact::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Receipt Customer',
            'email' => 'receipt@example.com',
            'type' => 'customer',
        ]);

        $invoice = Invoice::create([
            'tenant_id' => $this->tenant->id,
            'invoice_number' => 'INV-2025-0002',
            'contact_id' => $contact->id,
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'status' => 'paid',
            'subtotal' => 500.00,
            'tax_amount' => 0.00,
            'total' => 500.00,
            'payment_status' => 'paid',
        ]);

        $cashCoa = ChartOfAccount::create([
            'tenant_id' => $this->tenant->id,
            'code' => '1400',
            'name' => 'Cash',
            'type' => 'asset',
            'is_active' => true,
        ]);

        $mpesaAccount = Account::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'M-Pesa',
            'type' => 'mpesa',
            'chart_of_account_id' => $cashCoa->id,
            'is_active' => true,
        ]);

        $payment = Payment::create([
            'tenant_id' => $this->tenant->id,
            'invoice_id' => $invoice->id,
            'contact_id' => $contact->id,
            'payment_number' => 'PAY-' . date('Ymd') . '-0001',
            'payment_date' => now(),
            'account_id' => $mpesaAccount->id,
            'amount' => 500.00,
            'payment_method' => 'mpesa',
            'transaction_status' => 'pending',
        ]);

        PaymentAllocation::create([
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'amount' => 500.00,
        ]);

        $sentPayload = null;
        $this->mock(PHPMailerService::class, function ($mock) use (&$sentPayload) {
            $mock->shouldReceive('send')
                ->once()
                ->andReturnUsing(function (array $data) use (&$sentPayload) {
                    $sentPayload = $data;
                    return true;
                });
        });

        $response = $this->actingAs($this->user, 'web')
            ->post(route('tenant.invoices.send-receipt', $invoice), [
                '_token' => csrf_token(),
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertNotNull($sentPayload);
        $this->assertArrayHasKey('attachments', $sentPayload);
        $this->assertIsArray($sentPayload['attachments']);
        $this->assertCount(1, $sentPayload['attachments']);
        $this->assertStringContainsString('Receipt-Invoice-INV-2025-0002', $sentPayload['attachments'][0]);
        $this->assertEquals('receipt@example.com', $sentPayload['to']);
        $this->assertStringContainsString('INV-2025-0002', $sentPayload['subject']);
    }
}
