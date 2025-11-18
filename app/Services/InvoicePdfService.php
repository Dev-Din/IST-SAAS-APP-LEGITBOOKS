<?php

namespace App\Services;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoicePdfService
{
    public function render(Invoice $invoice)
    {
        $tenant = app(TenantContext::class)->getTenant();
        $data = [
            'invoice' => $invoice->load('lineItems', 'contact'),
            'tenant' => $tenant,
        ];

        $pdf = Pdf::loadView('tenant.invoices.pdf', $data);
        return $pdf->output();
    }
}
