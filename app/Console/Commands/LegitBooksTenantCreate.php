<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\TenantProvisioningService;
use Illuminate\Console\Command;

class LegitBooksTenantCreate extends Command
{
    protected $signature = 'legitbooks:tenant:create {name} {email} {--admin-email=} {--admin-password=} {--seed-demo}';

    protected $description = 'Create a new tenant with provisioning';

    public function handle(TenantProvisioningService $provisioningService)
    {
        $name = $this->argument('name');
        $email = $this->argument('email');
        $adminEmail = $this->option('admin-email') ?? $email;
        $adminPassword = $this->option('admin-password');
        $seedDemo = $this->option('seed-demo');

        $tenant = Tenant::create([
            'name' => $name,
            'email' => $email,
            'tenant_hash' => Tenant::generateTenantHash(),
            'status' => 'active',
            'settings' => [
                'branding_override' => null,
            ],
        ]);

        $this->info("Tenant created: {$tenant->name}");
        $this->info("Tenant Hash: {$tenant->tenant_hash}");

        $provisioningService->provision($tenant, [
            'create_admin' => true,
            'admin_email' => $adminEmail,
            'admin_password' => $adminPassword,
            'seed_demo_data' => $seedDemo,
        ]);

        $this->info('Tenant provisioned successfully!');
        $this->info("Access URL: /app/{$tenant->tenant_hash}/dashboard");

        return Command::SUCCESS;
    }
}
