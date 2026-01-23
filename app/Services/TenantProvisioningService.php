<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\Contact;
use App\Models\InvoiceCounter;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TenantProvisioningService
{
    public function provision(Tenant $tenant, array $options = []): void
    {
        DB::transaction(function () use ($tenant, $options) {
            // Create invoice counter
            InvoiceCounter::create([
                'tenant_id' => $tenant->id,
                'sequence' => 0,
            ]);

            // Chart of Accounts - tenants will create their own accounts
            // Default accounts are no longer seeded automatically
            // $this->seedDefaultCOA($tenant);

            // Create tenant admin user if requested
            if ($options['create_admin'] ?? false) {
                $this->createTenantAdmin($tenant, $options['admin_email'] ?? $tenant->email, $options['admin_password'] ?? null);
            }

            // Seed demo data if requested
            if ($options['seed_demo_data'] ?? false) {
                $this->seedDemoData($tenant);
            }
        });
    }

    protected function seedDefaultCOA(Tenant $tenant): void
    {
        $accounts = [
            ['code' => '1000', 'name' => 'Assets', 'type' => 'asset', 'category' => null],
            ['code' => '1100', 'name' => 'Current Assets', 'type' => 'asset', 'category' => 'current_asset', 'parent_code' => '1000'],
            ['code' => '1200', 'name' => 'Accounts Receivable', 'type' => 'asset', 'category' => 'current_asset', 'parent_code' => '1100'],
            ['code' => '1300', 'name' => 'Bank', 'type' => 'asset', 'category' => 'current_asset', 'parent_code' => '1100'],
            ['code' => '1400', 'name' => 'Cash', 'type' => 'asset', 'category' => 'current_asset', 'parent_code' => '1100'],
            ['code' => '2000', 'name' => 'Liabilities', 'type' => 'liability', 'category' => null],
            ['code' => '2100', 'name' => 'Current Liabilities', 'type' => 'liability', 'category' => 'current_liability', 'parent_code' => '2000'],
            ['code' => '2200', 'name' => 'Accounts Payable', 'type' => 'liability', 'category' => 'current_liability', 'parent_code' => '2100'],
            ['code' => '3000', 'name' => 'Equity', 'type' => 'equity', 'category' => 'equity'],
            ['code' => '4000', 'name' => 'Revenue', 'type' => 'revenue', 'category' => 'revenue'],
            ['code' => '4100', 'name' => 'Sales Revenue', 'type' => 'revenue', 'category' => 'revenue', 'parent_code' => '4000'],
            ['code' => '5000', 'name' => 'Expenses', 'type' => 'expense', 'category' => 'expense'],
        ];

        $parentMap = [];

        foreach ($accounts as $account) {
            $parentId = null;
            if (isset($account['parent_code'])) {
                $parentId = $parentMap[$account['parent_code']] ?? null;
            }

            $coa = ChartOfAccount::create([
                'tenant_id' => $tenant->id,
                'code' => $account['code'],
                'name' => $account['name'],
                'type' => $account['type'],
                'category' => $account['category'],
                'parent_id' => $parentId,
                'is_active' => true,
            ]);

            $parentMap[$account['code']] = $coa->id;
        }
    }

    protected function createTenantAdmin(Tenant $tenant, string $email, ?string $password = null): void
    {
        $password = $password ?? \Illuminate\Support\Str::random(12);

        User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin',
            'email' => $email,
            'password' => Hash::make($password),
            'is_active' => true,
        ]);
    }

    protected function seedDemoData(Tenant $tenant): void
    {
        // Demo contact
        Contact::create([
            'tenant_id' => $tenant->id,
            'name' => 'Demo Customer',
            'email' => 'demo@example.com',
            'phone' => '+254700000000',
            'type' => 'customer',
        ]);

        // Demo product - only create if a sales account exists
        // Since default COA is no longer seeded, this will only work if tenant has created accounts
        $salesAccount = ChartOfAccount::where('tenant_id', $tenant->id)
            ->where('type', 'revenue')
            ->first();

        if ($salesAccount) {
            Product::create([
                'tenant_id' => $tenant->id,
                'name' => 'Demo Product',
                'sku' => 'DEMO-001',
                'description' => 'Demo product for testing',
                'price' => 1000.00,
                'sales_account_id' => $salesAccount->id,
                'is_active' => true,
            ]);
        }
    }
}
