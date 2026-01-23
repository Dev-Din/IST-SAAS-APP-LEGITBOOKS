<?php

namespace Database\Seeders;

use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class BackfillDefaultSubscriptionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            // Check if subscription already exists (idempotent)
            $existingSubscription = Subscription::where('tenant_id', $tenant->id)->first();

            if (! $existingSubscription) {
                Subscription::create([
                    'tenant_id' => $tenant->id,
                    'plan' => 'plan_free',
                    'status' => 'active',
                    'started_at' => now(),
                    'ends_at' => null,
                    'trial_ends_at' => null,
                    'next_billing_at' => null,
                    'vat_applied' => false,
                ]);

                $this->command->info("Created plan_free subscription for tenant: {$tenant->name} (ID: {$tenant->id})");
            } else {
                $this->command->info("Tenant {$tenant->name} (ID: {$tenant->id}) already has a subscription, skipping.");
            }
        }

        $this->command->info('Backfill completed!');
    }
}
