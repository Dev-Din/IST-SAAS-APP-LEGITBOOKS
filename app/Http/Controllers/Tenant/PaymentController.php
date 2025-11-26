<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\TenantContext;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * Display a listing of payments.
     */
    public function index(TenantContext $tenantContext)
    {
        $tenant = $tenantContext->getTenant();
        
        $payments = Payment::where('tenant_id', $tenant->id)
            ->with(['invoice', 'subscription', 'account', 'contact'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('tenant.payments.index', compact('payments'));
    }

    /**
     * Show the form for creating a new payment.
     */
    public function create()
    {
        // Manual payment creation - redirect to receipts page for M-Pesa
        return redirect()->route('tenant.payments.receipts');
    }

    /**
     * Store a newly created payment.
     */
    public function store(Request $request)
    {
        // Payments are created via M-Pesa callbacks or invoice payments
        return redirect()->route('tenant.payments.index')
            ->with('info', 'Payments are automatically created when received via M-Pesa or other payment methods.');
    }

    /**
     * Display the specified payment.
     */
    public function show($id, TenantContext $tenantContext)
    {
        $tenant = $tenantContext->getTenant();
        
        $payment = Payment::where('id', $id)
            ->where('tenant_id', $tenant->id)
            ->with(['invoice', 'subscription', 'account', 'contact', 'allocations'])
            ->firstOrFail();

        return view('tenant.payments.show', compact('payment'));
    }

    /**
     * Show the form for editing the specified payment.
     */
    public function edit($id)
    {
        // Payments are not editable - they come from external sources
        return redirect()->route('tenant.payments.show', $id)
            ->with('info', 'Payments cannot be edited as they are automatically created from payment confirmations.');
    }

    /**
     * Update the specified payment.
     */
    public function update(Request $request, $id)
    {
        // Payments are not editable
        return redirect()->route('tenant.payments.show', $id)
            ->with('error', 'Payments cannot be updated.');
    }

    /**
     * Remove the specified payment.
     */
    public function destroy($id)
    {
        // Payments should not be deleted - they are financial records
        return redirect()->route('tenant.payments.index')
            ->with('error', 'Payments cannot be deleted as they are permanent financial records.');
    }
}
