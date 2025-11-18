<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\TenantContext;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(TenantContext $tenantContext)
    {
        $tenant = $tenantContext->getTenant();
        
        $stats = [
            'total_invoices' => \App\Models\Invoice::count(),
            'total_payments' => \App\Models\Payment::count(),
            'total_contacts' => \App\Models\Contact::count(),
            'outstanding_invoices' => \App\Models\Invoice::where('status', '!=', 'paid')->count(),
        ];

        return view('tenant.dashboard', compact('tenant', 'stats'));
    }
}
