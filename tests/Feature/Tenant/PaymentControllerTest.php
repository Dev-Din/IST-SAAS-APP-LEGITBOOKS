<?php

namespace Tests\Feature\Tenant;

use App\Models\Account;
use App\Models\ChartOfAccount;
use App\Models\Contact;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PaymentControllerTest extends TestCase
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

        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
            'is_owner' => true,
            'permissions' => ['manage_payments', 'view_payments'],
        ]);

        app(\App\Services\TenantContext::class)->setTenant($this->tenant);
    }

    public function test_index_displays_payments(): void
    {
        $coa = ChartOfAccount::create([
            'tenant_id' => $this->tenant->id,
            'code' => '1300',
            'name' => 'Bank',
            'type' => 'asset',
            'category' => 'current_asset',
            'is_active' => true,
        ]);

        $account = Account::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Bank',
            'type' => 'bank',
            'chart_of_account_id' => $coa->id,
            'is_active' => true,
        ]);

        Payment::create([
            'tenant_id' => $this->tenant->id,
            'payment_number' => 'PAY-001',
            'payment_date' => now(),
            'account_id' => $account->id,
            'amount' => 1000.00,
            'payment_method' => 'cash',
            'transaction_status' => 'completed',
        ]);

        $response = $this->actingAs($this->user, 'web')
            ->get(route('tenant.payments.index'));

        $response->assertStatus(200);
        $response->assertViewIs('tenant.payments.index');
    }

    public function test_show_displays_payment(): void
    {
        $coa = ChartOfAccount::create([
            'tenant_id' => $this->tenant->id,
            'code' => '1300',
            'name' => 'Bank',
            'type' => 'asset',
            'category' => 'current_asset',
            'is_active' => true,
        ]);

        $account = Account::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Bank',
            'type' => 'bank',
            'chart_of_account_id' => $coa->id,
            'is_active' => true,
        ]);

        $payment = Payment::create([
            'tenant_id' => $this->tenant->id,
            'payment_number' => 'PAY-001',
            'payment_date' => now(),
            'account_id' => $account->id,
            'amount' => 1000.00,
            'payment_method' => 'cash',
            'transaction_status' => 'completed',
        ]);

        $response = $this->actingAs($this->user, 'web')
            ->get(route('tenant.payments.show', $payment->id));

        $response->assertStatus(200);
        $response->assertViewIs('tenant.payments.show');
    }
}
