<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Services\InvoiceNumberService;
use App\Services\InvoicePostingService;

class InvoiceObserver
{
    public function creating(Invoice $invoice): void
    {
        if (!$invoice->invoice_number) {
            $invoice->invoice_number = app(InvoiceNumberService::class)->generateNextNumber();
        }
    }

    public function updated(Invoice $invoice): void
    {
        if ($invoice->isDirty('status') && $invoice->status === 'sent') {
            app(InvoicePostingService::class)->postInvoice($invoice->fresh(['lineItems', 'contact']));
        }
    }
}
