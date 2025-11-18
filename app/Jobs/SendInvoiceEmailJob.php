<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Services\EmailService;
use App\Services\InvoicePdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendInvoiceEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $invoiceId,
        public string $recipient
    ) {}

    public function handle(InvoicePdfService $pdfService, EmailService $emailService): void
    {
        $invoice = Invoice::with(['lineItems', 'contact'])->find($this->invoiceId);

        if (!$invoice) {
            return;
        }

        $html = view('emails.invoice', ['invoice' => $invoice])->render();
        $pdf = $pdfService->render($invoice);

        // For now, send HTML without attachment; integration can be extended
        $emailService->send($this->recipient, "Invoice {$invoice->invoice_number}", $html);
    }
}
