<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Services\TenantContext;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(TenantContext $tenantContext)
    {
        $tenant = $tenantContext->getTenant();
        $contacts = Contact::orderBy('name')->paginate(15);

        return view('tenant.contacts.index', compact('contacts', 'tenant'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(TenantContext $tenantContext)
    {
        $tenant = $tenantContext->getTenant();
        return view('tenant.contacts.create', compact('tenant'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, TenantContext $tenantContext)
    {
        $tenant = $tenantContext->getTenant();
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'type' => 'required|in:customer,supplier',
            'address' => 'nullable|string',
            'tax_id' => 'nullable|string|max:255',
        ]);

        Contact::create([
            'tenant_id' => $tenant->id,
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'type' => $validated['type'],
            'address' => $validated['address'] ?? null,
            'tax_id' => $validated['tax_id'] ?? null,
        ]);

        return redirect()->route('tenant.contacts.index')
            ->with('success', 'Contact created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Contact $contact, TenantContext $tenantContext)
    {
        $tenant = $tenantContext->getTenant();
        $contact->load('invoices', 'payments');
        
        return view('tenant.contacts.show', compact('contact', 'tenant'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Contact $contact, TenantContext $tenantContext)
    {
        $tenant = $tenantContext->getTenant();
        return view('tenant.contacts.edit', compact('contact', 'tenant'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Contact $contact, TenantContext $tenantContext)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'type' => 'required|in:customer,supplier',
            'address' => 'nullable|string',
            'tax_id' => 'nullable|string|max:255',
        ]);

        $contact->update($validated);

        return redirect()->route('tenant.contacts.show', $contact)
            ->with('success', 'Contact updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Contact $contact)
    {
        $contact->delete();
        return redirect()->route('tenant.contacts.index')
            ->with('success', 'Contact deleted successfully.');
    }
}
