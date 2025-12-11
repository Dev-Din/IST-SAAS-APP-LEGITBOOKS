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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
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

        $maxRetries = 3;
        $retryCount = 0;

        while ($retryCount < $maxRetries) {
            try {
                $result = DB::transaction(function () use ($tenant, $validated, $invoiceNumberService) {
                    // Generate invoice number with concurrency safety
                    try {
                        $invoiceNumber = $invoiceNumberService->generate($tenant->id);
                    } catch (RuntimeException $e) {
                        throw new \Exception('Unable to generate invoice number: ' . $e->getMessage());
                    }

                    // Check if invoice number already exists (race condition check)
                    if (Invoice::where('invoice_number', $invoiceNumber)->exists()) {
                        // If it exists, generate a new number
                        $invoiceNumber = $invoiceNumberService->generate($tenant->id);
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

                    return $invoice;
                });

                // If we get here, the transaction succeeded
                return redirect()->route('tenant.invoices.show', $result)
                    ->with('success', 'Invoice created successfully.');
                    
            } catch (QueryException $e) {
                // Check if it's a duplicate key error for invoice_number
                if ($e->getCode() == 23000 && str_contains($e->getMessage(), 'invoice_number')) {
                    $retryCount++;
                    if ($retryCount >= $maxRetries) {
                        return back()
                            ->withErrors(['invoice' => 'Unable to create invoice due to a duplicate invoice number. Please try again.'])
                            ->withInput();
                    }
                    // Retry with a new number
                    continue;
                }
                // If it's a different database error, log it and return with error
                \Log::error('Invoice creation failed', [
                    'error' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'trace' => $e->getTraceAsString()
                ]);
                return back()
                    ->withErrors(['invoice' => 'Database error occurred. Please try again or contact support.'])
                    ->withInput();
            } catch (\Exception $e) {
                \Log::error('Invoice creation failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return back()
                    ->withErrors(['invoice' => 'An error occurred: ' . $e->getMessage()])
                    ->withInput();
            }
        }

        return back()
            ->withErrors(['invoice' => 'Unable to create invoice after multiple attempts. Please try again.'])
            ->withInput();

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

    /**
     * Export invoices in various formats
     */
    public function export(Request $request, TenantContext $tenantContext)
    {
        $tenant = $tenantContext->getTenant();
        $format = $request->get('format', 'csv'); // csv, xlsx, pdf
        
        // Get all invoices for this tenant (not paginated for export)
        $invoices = Invoice::where('tenant_id', $tenant->id)
            ->with('contact', 'paymentAllocations')
            ->orderBy('invoice_date', 'desc')
            ->get();

        switch ($format) {
            case 'csv':
                return $this->exportCsv($invoices, $tenant);
            case 'pdf':
                return $this->exportPdf($invoices, $tenant);
            default:
                return redirect()->back()->withErrors(['export' => 'Invalid export format. Only CSV and PDF are supported.']);
        }
    }

    /**
     * Export invoices as CSV
     */
    protected function exportCsv($invoices, $tenant)
    {
        $filename = "invoices_" . now()->format('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($invoices) {
            $file = fopen('php://output', 'w');

            // Write CSV headers
            fputcsv($file, [
                'Invoice Number',
                'Date',
                'Due Date',
                'Customer',
                'Status',
                'Subtotal',
                'Tax',
                'Total',
                'Outstanding',
                'Payment Status'
            ]);

            // Write invoice data
            foreach ($invoices as $invoice) {
                $outstanding = $invoice->getOutstandingAmount();
                fputcsv($file, [
                    $invoice->invoice_number,
                    $invoice->invoice_date->format('d/m/Y'),
                    $invoice->due_date ? $invoice->due_date->format('d/m/Y') : 'N/A',
                    $invoice->contact->name,
                    ucfirst($invoice->status),
                    number_format($invoice->subtotal, 2),
                    number_format($invoice->tax_amount, 2),
                    number_format($invoice->total, 2),
                    number_format($outstanding, 2),
                    $outstanding <= 0 ? 'Paid' : ($outstanding < $invoice->total ? 'Partial' : 'Unpaid')
                ]);
            }

            fclose($file);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Export invoices as Excel
     */
    protected function exportExcel($invoices, $tenant)
    {
        // Check if Laravel Excel 3.x interfaces exist (modern version)
        // Must check BEFORE trying to load InvoiceExport class to avoid fatal error
        // The old version 1.1.5 doesn't have these interfaces
        if (!interface_exists('Maatwebsite\\Excel\\Concerns\\FromCollection')) {
            \Log::info('Laravel Excel 3.x not available (old version 1.x installed), using CSV export instead');
            // Return CSV but with .xlsx extension so user gets a file
            // They can rename it or we can change the extension
            $response = $this->exportCsv($invoices, $tenant);
            // Note: CSV response will have .csv extension, which is fine
            return $response;
        }

        // Try to use Excel export if interfaces are available
        // Use string class name and check if class can be safely loaded
        try {
            $exportClassName = 'App\\Exports\\InvoiceExport';
            
            // Only proceed if class exists and interfaces are available
            if (!class_exists($exportClassName, false)) {
                // Try with autoload, but this might fail if interfaces don't exist
                if (!class_exists($exportClassName, true)) {
                    throw new \RuntimeException('InvoiceExport class cannot be loaded');
                }
            }
            
            return \Maatwebsite\Excel\Facades\Excel::download(
                new $exportClassName($invoices),
                "invoices_" . now()->format('Y-m-d_His') . '.xlsx'
            );
        } catch (\Error $e) {
            // Catch fatal errors (class not found, interface not found, etc.)
            \Log::warning('Excel export failed due to missing dependencies, using CSV', [
                'error' => $e->getMessage(),
                'exception' => get_class($e)
            ]);
            return $this->exportCsv($invoices, $tenant);
        } catch (\Throwable $e) {
            \Log::warning('Excel export failed, falling back to CSV', [
                'error' => $e->getMessage(),
                'exception' => get_class($e)
            ]);
            return $this->exportCsv($invoices, $tenant);
        }
    }

    /**
     * Export invoices as PDF
     */
    protected function exportPdf($invoices, $tenant)
    {
        $html = view('tenant.invoices.export-pdf', compact('invoices', 'tenant'))->render();
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
        
        $filename = "invoices_" . now()->format('Y-m-d_His') . '.pdf';
        
        return $pdf->download($filename);
    }
}
