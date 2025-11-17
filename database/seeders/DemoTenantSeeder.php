<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantProvisioningService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoTenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['email' => 'demo@tenant.com'],
            [
                'name' => 'Demo Tenant',
                'tenant_hash' => Tenant::generateTenantHash(),
                'status' => 'active',
                'settings' => [
                    'branding_override' => null,
                    'brand' => [
                        'name' => 'Demo Tenant',
                        'logo_path' => null,
                        'primary_color' => '#392a26',
                        'text_color' => '#ffffff',
                    ],
                ],
            ]
        );

        // Provision tenant
        $provisioningService = app(TenantProvisioningService::class);
        $provisioningService->provision($tenant, [
            'create_admin' => true,
            'admin_email' => 'admin@demo.com',
            'admin_password' => 'password',
            'seed_demo_data' => true,
        ]);

        // Create demo tenant user
        User::firstOrCreate(
            ['email' => 'user@demo.com', 'tenant_id' => $tenant->id],
            [
                'name' => 'Demo User',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );
    }
}
