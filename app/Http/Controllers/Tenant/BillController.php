<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Contact;
use App\Models\Product;
use App\Models\ChartOfAccount;
use App\Services\TenantContext;
use App\Services\BillNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use RuntimeException;

class BillController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(TenantContext $tenantContext)
    {
        $tenant = $tenantContext->getTenant();
        $bills = Bill::with('contact')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('tenant.bills.index', compact('bills', 'tenant'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(TenantContext $tenantContext)
    {
        $tenant = $tenantContext->getTenant();
        // Get suppliers only
        $contacts = Contact::whereIn('type', ['supplier', 'both'])
            ->orderBy('name')
            ->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();
        // Get expense accounts for bills
        $expenseAccounts = ChartOfAccount::where('type', 'expense')->orderBy('name')->get();

        return view('tenant.bills.create', compact('tenant', 'contacts', 'products', 'expenseAccounts'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, TenantContext $tenantContext, BillNumberService $billNumberService)
    {
        $tenant = $tenantContext->getTenant();
        
        $validated = $request->validate([
            'contact_id' => 'required|exists:contacts,id',
            'bill_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:bill_date',
            'line_items' => 'required|array|min:1',
            'line_items.*.description' => 'required|string',
            'line_items.*.quantity' => 'required|numeric|min:0.01',
            'line_items.*.unit_price' => 'required|numeric|min:0',
            'line_items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'line_items.*.product_id' => 'nullable|exists:products,id',
            'line_items.*.expense_account_id' => 'nullable|exists:chart_of_accounts,id',
            'notes' => 'nullable|string',
        ]);

        $maxRetries = 3;
        $retryCount = 0;

        while ($retryCount < $maxRetries) {
            try {
                $result = DB::transaction(function () use ($tenant, $validated, $billNumberService) {
                    // Generate bill number with concurrency safety
                    try {
                        $billNumber = $billNumberService->generate($tenant->id);
                    } catch (RuntimeException $e) {
                        throw new \Exception('Unable to generate bill number: ' . $e->getMessage());
                    }

                    // Check if bill number already exists (race condition check)
                    if (Bill::where('bill_number', $billNumber)->where('tenant_id', $tenant->id)->exists()) {
                        // If it exists, generate a new number
                        $billNumber = $billNumberService->generate($tenant->id);
                    }

                    $bill = Bill::create([
                        'tenant_id' => $tenant->id,
                        'bill_number' => $billNumber,
                        'contact_id' => $validated['contact_id'],
                        'bill_date' => $validated['bill_date'],
                        'due_date' => $validated['due_date'] ?? null,
                        'status' => 'draft',
                        'notes' => $validated['notes'] ?? null,
                    ]);

                    $subtotal = 0;
                    $taxAmount = 0;

                    foreach ($validated['line_items'] as $item) {
                        $lineTotal = $item['quantity'] * $item['unit_price'];
                        $lineTax = $lineTotal * ($item['tax_rate'] ?? 0) / 100;
                        
                        $bill->lineItems()->create([
                            'product_id' => $item['product_id'] ?? null,
                            'description' => $item['description'],
                            'quantity' => $item['quantity'],
                            'unit_price' => $item['unit_price'],
                            'tax_rate' => $item['tax_rate'] ?? 0,
                            'line_total' => $lineTotal + $lineTax,
                            'expense_account_id' => $item['expense_account_id'] ?? null,
                        ]);

                        $subtotal += $lineTotal;
                        $taxAmount += $lineTax;
                    }

                    $bill->update([
                        'subtotal' => $subtotal,
                        'tax_amount' => $taxAmount,
                        'total' => $subtotal + $taxAmount,
                    ]);

                    return $bill;
                });

                // If we get here, the transaction succeeded
                return redirect()->route('tenant.bills.show', $result)
                    ->with('success', 'Bill created successfully.');
                    
            } catch (QueryException $e) {
                // Check if it's a duplicate key error for bill_number
                if ($e->getCode() == 23000 && str_contains($e->getMessage(), 'bill_number')) {
                    $retryCount++;
                    if ($retryCount >= $maxRetries) {
                        return back()
                            ->withErrors(['bill' => 'Unable to create bill due to a duplicate bill number. Please try again.'])
                            ->withInput();
                    }
                    // Retry with a new number
                    continue;
                }
                // If it's a different error, throw it
                throw $e;
            } catch (\Exception $e) {
                return back()
                    ->withErrors(['bill' => 'An error occurred: ' . $e->getMessage()])
                    ->withInput();
            }
        }

        return back()
            ->withErrors(['bill' => 'Unable to create bill after multiple attempts. Please try again.'])
            ->withInput();
    }

    /**
     * Display the specified resource.
     */
    public function show(Bill $bill, TenantContext $tenantContext)
    {
        $tenant = $tenantContext->getTenant();
        $bill->load('lineItems', 'contact', 'paymentAllocations.payment', 'tenant');
        
        return view('tenant.bills.show', compact('bill', 'tenant'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Bill $bill, TenantContext $tenantContext)
    {
        $tenant = $tenantContext->getTenant();
        $bill->load('lineItems');
        $contacts = Contact::whereIn('type', ['supplier', 'both'])->orderBy('name')->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();
        $expenseAccounts = ChartOfAccount::where('type', 'expense')->orderBy('name')->get();

        return view('tenant.bills.edit', compact('bill', 'tenant', 'contacts', 'products', 'expenseAccounts'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Bill $bill, TenantContext $tenantContext)
    {
        $validated = $request->validate([
            'contact_id' => 'required|exists:contacts,id',
            'bill_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:bill_date',
            'line_items' => 'required|array|min:1',
            'line_items.*.description' => 'required|string',
            'line_items.*.quantity' => 'required|numeric|min:0.01',
            'line_items.*.unit_price' => 'required|numeric|min:0',
            'line_items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'line_items.*.product_id' => 'nullable|exists:products,id',
            'line_items.*.expense_account_id' => 'nullable|exists:chart_of_accounts,id',
            'notes' => 'nullable|string',
        ]);

        // Check if bill has been paid
        if ($bill->status === 'paid' && $bill->paymentAllocations()->sum('amount') > 0) {
            return back()
                ->withErrors(['bill' => 'Cannot edit a bill that has been paid.'])
                ->withInput();
        }

        DB::transaction(function () use ($bill, $validated) {
            $bill->update([
                'contact_id' => $validated['contact_id'],
                'bill_date' => $validated['bill_date'],
                'due_date' => $validated['due_date'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Delete existing line items
            $bill->lineItems()->delete();

            $subtotal = 0;
            $taxAmount = 0;

            // Create new line items
            foreach ($validated['line_items'] as $item) {
                $lineTotal = $item['quantity'] * $item['unit_price'];
                $lineTax = $lineTotal * ($item['tax_rate'] ?? 0) / 100;
                
                $bill->lineItems()->create([
                    'product_id' => $item['product_id'] ?? null,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'tax_rate' => $item['tax_rate'] ?? 0,
                    'line_total' => $lineTotal + $lineTax,
                    'expense_account_id' => $item['expense_account_id'] ?? null,
                ]);

                $subtotal += $lineTotal;
                $taxAmount += $lineTax;
            }

            $bill->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total' => $subtotal + $taxAmount,
            ]);
        });

        return redirect()->route('tenant.bills.show', $bill)
            ->with('success', 'Bill updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Bill $bill)
    {
        // Check if bill has payments
        if ($bill->paymentAllocations()->count() > 0) {
            return redirect()->route('tenant.bills.index')
                ->with('error', 'Cannot delete a bill that has payments allocated.');
        }

        $bill->delete();
        
        return redirect()->route('tenant.bills.index')
            ->with('success', 'Bill deleted successfully.');
    }

    /**
     * Mark bill as received
     */
    public function markReceived(Bill $bill, TenantContext $tenantContext)
    {
        if ($bill->status === 'draft') {
            $bill->update(['status' => 'received']);
            return redirect()->route('tenant.bills.show', $bill)
                ->with('success', 'Bill marked as received.');
        }

        return redirect()->route('tenant.bills.show', $bill)
            ->with('error', 'Only draft bills can be marked as received.');
    }
}

