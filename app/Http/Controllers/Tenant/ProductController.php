<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\Product;
use App\Services\TenantContext;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(TenantContext $tenantContext)
    {
        $tenant = $tenantContext->getTenant();
        $products = Product::where('tenant_id', $tenant->id)
            ->with('salesAccount')
            ->orderBy('name')
            ->paginate(15);

        return view('tenant.products.index', compact('products', 'tenant'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(TenantContext $tenantContext)
    {
        $tenant = $tenantContext->getTenant();
        $salesAccounts = ChartOfAccount::where('tenant_id', $tenant->id)
            ->where('type', 'revenue')
            ->orderBy('name')
            ->get();

        return view('tenant.products.create', compact('tenant', 'salesAccounts'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, TenantContext $tenantContext)
    {
        $tenant = $tenantContext->getTenant();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'sales_account_id' => 'nullable|exists:chart_of_accounts,id',
            'is_active' => 'boolean',
        ]);

        Product::create([
            'tenant_id' => $tenant->id,
            'name' => $validated['name'],
            'sku' => $validated['sku'] ?? null,
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'sales_account_id' => $validated['sales_account_id'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('tenant.products.index')
            ->with('success', 'Product created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product, TenantContext $tenantContext)
    {
        $tenant = $tenantContext->getTenant();
        $product->load('salesAccount', 'invoiceLineItems.invoice');

        return view('tenant.products.show', compact('product', 'tenant'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product, TenantContext $tenantContext)
    {
        $tenant = $tenantContext->getTenant();
        $salesAccounts = ChartOfAccount::where('tenant_id', $tenant->id)
            ->where('type', 'revenue')
            ->orderBy('name')
            ->get();

        return view('tenant.products.edit', compact('product', 'tenant', 'salesAccounts'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product, TenantContext $tenantContext)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'sales_account_id' => 'nullable|exists:chart_of_accounts,id',
            'is_active' => 'boolean',
        ]);

        $product->update($validated);

        return redirect()->route('tenant.products.show', $product)
            ->with('success', 'Product updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        // Check if product is used in invoices
        if ($product->invoiceLineItems()->count() > 0) {
            return redirect()->route('tenant.products.index')
                ->with('error', 'Cannot delete product that has been used in invoices.');
        }

        $product->delete();

        return redirect()->route('tenant.products.index')
            ->with('success', 'Product deleted successfully.');
    }
}
