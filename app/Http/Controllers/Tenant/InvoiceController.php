<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Contact;
use App\Models\Product;
use App\Models\ChartOfAccount;
use App\Services\TenantContext;
use App\Services\InvoiceNumberService;
use App\Services\InvoiceSendService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(TenantContext $tenantContext)
    {
        $tenant = $tenantContext->getTenant();
        $invoices = Invoice::with('contact')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('tenant.invoices.index', compact('invoices', 'tenant'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(TenantContext $tenantContext)
    {
        $tenant = $tenantContext->getTenant();
        $contacts = Contact::orderBy('name')->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();
        $salesAccounts = ChartOfAccount::where('type', 'revenue')->orderBy('name')->get();

        return view('tenant.invoices.create', compact('tenant', 'contacts', 'products', 'salesAccounts'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, TenantContext $tenantContext, InvoiceNumberService $invoiceNumberService)
    {
        $tenant = $tenantContext->getTenant();
        
        $validated = $request->validate([
            'contact_id' => 'required|exists:contacts,id',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:invoice_date',
            'line_items' => 'required|array|min:1',
            'line_items.*.description' => 'required|string',
            'line_items.*.quantity' => 'required|numeric|min:0.01',
            'line_items.*.unit_price' => 'required|numeric|min:0',
            'line_items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'line_items.*.product_id' => 'nullable|exists:products,id',
            'line_items.*.sales_account_id' => 'nullable|exists:chart_of_accounts,id',
            'notes' => 'nullable|string',
        ]);

        try {
            // Generate invoice number with concurrency safety
            $invoiceNumber = $invoiceNumberService->generate($tenant->id);
        } catch (RuntimeException $e) {
            return back()
                ->withErrors(['invoice' => 'Unable to generate invoice number, please try again.'])
                ->withInput();
        }

        $invoice = Invoice::create([
            'tenant_id' => $tenant->id,
            'invoice_number' => $invoiceNumber,
            'contact_id' => $validated['contact_id'],
            'invoice_date' => $validated['invoice_date'],
            'due_date' => $validated['due_date'] ?? null,
            'status' => 'draft',
            'notes' => $validated['notes'] ?? null,
        ]);

        $subtotal = 0;
        $taxAmount = 0;

        foreach ($validated['line_items'] as $item) {
            $lineTotal = $item['quantity'] * $item['unit_price'];
            $lineTax = $lineTotal * ($item['tax_rate'] ?? 0) / 100;
            
            $invoice->lineItems()->create([
                'product_id' => $item['product_id'] ?? null,
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'tax_rate' => $item['tax_rate'] ?? 0,
                'line_total' => $lineTotal + $lineTax,
                'sales_account_id' => $item['sales_account_id'] ?? null,
            ]);

            $subtotal += $lineTotal;
            $taxAmount += $lineTax;
        }

        $invoice->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $subtotal + $taxAmount,
        ]);

        return redirect()->route('tenant.invoices.show', $invoice)
            ->with('success', 'Invoice created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Invoice $invoice, TenantContext $tenantContext)
    {
        $tenant = $tenantContext->getTenant();
        $invoice->load('lineItems', 'contact', 'paymentAllocations.payment', 'tenant');
        
        return view('tenant.invoices.show', compact('invoice', 'tenant'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Invoice $invoice, TenantContext $tenantContext)
    {
        $tenant = $tenantContext->getTenant();
        $invoice->load('lineItems');
        $contacts = Contact::orderBy('name')->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();
        $salesAccounts = ChartOfAccount::where('type', 'revenue')->orderBy('name')->get();

        return view('tenant.invoices.edit', compact('invoice', 'tenant', 'contacts', 'products', 'salesAccounts'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Invoice $invoice, TenantContext $tenantContext)
    {
        $validated = $request->validate([
            'contact_id' => 'required|exists:contacts,id',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:invoice_date',
            'status' => 'required|in:draft,sent,paid,overdue,cancelled',
            'line_items' => 'required|array|min:1',
            'line_items.*.description' => 'required|string',
            'line_items.*.quantity' => 'required|numeric|min:0.01',
            'line_items.*.unit_price' => 'required|numeric|min:0',
            'line_items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'line_items.*.product_id' => 'nullable|exists:products,id',
            'line_items.*.sales_account_id' => 'nullable|exists:chart_of_accounts,id',
            'notes' => 'nullable|string',
        ]);

        $invoice->update([
            'contact_id' => $validated['contact_id'],
            'invoice_date' => $validated['invoice_date'],
            'due_date' => $validated['due_date'] ?? null,
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
        ]);

        // Delete existing line items
        $invoice->lineItems()->delete();

        $subtotal = 0;
        $taxAmount = 0;

        foreach ($validated['line_items'] as $item) {
            $lineTotal = $item['quantity'] * $item['unit_price'];
            $lineTax = $lineTotal * ($item['tax_rate'] ?? 0) / 100;
            
            $invoice->lineItems()->create([
                'product_id' => $item['product_id'] ?? null,
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'tax_rate' => $item['tax_rate'] ?? 0,
                'line_total' => $lineTotal + $lineTax,
                'sales_account_id' => $item['sales_account_id'] ?? null,
            ]);

            $subtotal += $lineTotal;
            $taxAmount += $lineTax;
        }

        $invoice->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $subtotal + $taxAmount,
        ]);

        return redirect()->route('tenant.invoices.show', $invoice)
            ->with('success', 'Invoice updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Invoice $invoice)
    {
        $invoice->delete();
        return redirect()->route('tenant.invoices.index')
            ->with('success', 'Invoice deleted successfully.');
    }

    public function downloadPdf(Invoice $invoice, TenantContext $tenantContext)
    {
        $tenant = $tenantContext->getTenant();
        $invoice->load('lineItems', 'contact');
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('tenant.invoices.pdf', compact('invoice', 'tenant'));
        return $pdf->download("invoice-{$invoice->invoice_number}.pdf");
    }

    public function receipt(Invoice $invoice, TenantContext $tenantContext)
    {
        $tenant = $tenantContext->getTenant();
        $invoice->load('lineItems', 'contact', 'paymentAllocations.payment');
        
        return view('tenant.invoices.receipt', compact('invoice', 'tenant'));
    }

    public function sendEmail(Invoice $invoice, TenantContext $tenantContext, InvoiceSendService $sendService)
    {
        $tenant = $tenantContext->getTenant();

        // Validate invoice can be sent
        if ($invoice->status === 'paid') {
            return redirect()->back()->withErrors(['invoice' => 'Cannot send an invoice that is already paid.']);
        }

        if ($invoice->status === 'cancelled') {
            return redirect()->back()->withErrors(['invoice' => 'Cannot send a cancelled invoice.']);
        }

        if (!$invoice->contact->email) {
            return redirect()->back()->withErrors(['invoice' => 'Contact does not have an email address.']);
        }

        try {
            $result = $sendService->sendInvoice($invoice, auth()->id());

            if ($result['success']) {
                $message = 'Invoice sent successfully.';
                if ($result['email_status'] === 'failed') {
                    $message .= ' However, the email failed to send. You can try resending.';
                }
                return redirect()->route('tenant.invoices.show', $invoice)
                    ->with('success', $message);
            } else {
                return redirect()->back()->withErrors(['invoice' => 'Failed to send invoice. Please try again.']);
            }
        } catch (\Exception $e) {
            \Log::error('Invoice send failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->withErrors(['invoice' => 'An error occurred while sending the invoice: ' . $e->getMessage()]);
        }
    }
}
