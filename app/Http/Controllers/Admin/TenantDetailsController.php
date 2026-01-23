<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;

class TenantDetailsController extends Controller
{
    /**
     * Check if admin has permission to manage tenants
     */
    protected function ensurePermission(): void
    {
        $admin = Auth::guard('admin')->user();
        if (! $admin || (! $admin->hasRole('owner') && ! $admin->hasPermission('tenants.view'))) {
            abort(403, 'You do not have permission to view tenant details.');
        }
    }

    /**
     * Get tenant details for expanded card (AJAX)
     */
    public function show(Tenant $tenant)
    {
        $this->ensurePermission();

        // Eager load relationships
        $tenant->load([
            'users' => function ($query) {
                $query->with('role')->orderBy('is_owner', 'desc')->orderBy('created_at', 'desc');
            },
            'subscription',
            'invoices' => function ($query) {
                $query->with('contact')->latest()->limit(5);
            },
        ]);

        // Get summary counts
        $usersCount = $tenant->users()->count();
        $invoicesCount = $tenant->invoices()->count();
        $paidInvoicesCount = $tenant->invoices()->where('status', 'paid')->count();
        $dueInvoicesCount = $tenant->invoices()
            ->where('status', '!=', 'paid')
            ->where('due_date', '>=', now())
            ->count();
        $overdueInvoicesCount = $tenant->invoices()
            ->where('status', '!=', 'paid')
            ->where('due_date', '<', now())
            ->count();

        return response()->json([
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'email' => $tenant->email,
                'status' => $tenant->status,
                'tenant_hash' => $tenant->tenant_hash,
            ],
            'subscription' => $tenant->subscription ? [
                'plan' => $tenant->subscription->plan,
                'plan_name' => $this->getPlanName($tenant->subscription->plan),
                'status' => $tenant->subscription->status,
                'started_at' => $tenant->subscription->started_at?->format('d/m/Y'),
                'next_billing_at' => $tenant->subscription->next_billing_at?->format('d/m/Y'),
                'payment_method' => $tenant->subscription->getMaskedPaymentDisplay(),
            ] : null,
            'summary' => [
                'users_count' => $usersCount,
                'invoices_count' => $invoicesCount,
                'paid_invoices_count' => $paidInvoicesCount,
                'due_invoices_count' => $dueInvoicesCount,
                'overdue_invoices_count' => $overdueInvoicesCount,
            ],
        ]);
    }

    /**
     * Get plan name from plan ID
     */
    protected function getPlanName(string $plan): string
    {
        return match ($plan) {
            'plan_free' => 'Free',
            'plan_starter' => 'Starter',
            'plan_business' => 'Business',
            'plan_enterprise' => 'Enterprise',
            default => ucfirst(str_replace('plan_', '', $plan)),
        };
    }
}
