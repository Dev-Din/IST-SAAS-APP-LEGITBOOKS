<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Invoice;
use App\Services\Mail\PHPMailerService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InvoiceSendService
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected PHPMailerService $mailer,
        protected InvoicePostingService $postingService
    ) {}

    /**
     * Send invoice: generate PDF, send email, update status, create journal entry
     */
    public function sendInvoice(Invoice $invoice, ?int $userId = null): array
    {
        $tenant = $this->tenantContext->getTenant();

        return DB::transaction(function () use ($invoice, $tenant, $userId) {
            try {
                // Load relationships
                $invoice->load('lineItems', 'contact', 'tenant');

                // 1. Generate PDF
                $pdfPath = $this->generatePdf($invoice);

                // 2. Generate payment token if not exists
                $paymentToken = $invoice->payment_token;
                if (! $paymentToken) {
                    $paymentToken = $this->generatePaymentToken();
                }

                // 3. Compose and send email (use token for payment URL)
                $emailResult = $this->sendInvoiceEmail($invoice, $pdfPath, $paymentToken);

                // 4. Update invoice status (including payment_token)
                $invoice->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                    'pdf_path' => $pdfPath,
                    'payment_token' => $paymentToken,
                    'mail_status' => $emailResult['status'],
                    'mail_message_id' => $emailResult['message_id'] ?? null,
                ]);

                // 5. Create journal entry (accrual basis)
                if (! $invoice->journalEntry) {
                    $this->postingService->postInvoice($invoice);
                }

                // 6. Create audit log
                AuditLog::create([
                    'tenant_id' => $tenant->id,
                    'model_type' => Invoice::class,
                    'model_id' => $invoice->id,
                    'performed_by' => $userId ?? auth()->id(),
                    'action' => 'sent',
                    'after' => [
                        'status' => 'sent',
                        'sent_at' => $invoice->sent_at->toIso8601String(),
                        'mail_status' => $emailResult['status'],
                    ],
                ]);

                Log::info('Invoice sent successfully', [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'email_status' => $emailResult['status'],
                ]);

                return [
                    'success' => true,
                    'invoice' => $invoice->fresh(),
                    'email_status' => $emailResult['status'],
                    'pdf_path' => $pdfPath,
                ];
            } catch (\Exception $e) {
                Log::error('Failed to send invoice', [
                    'invoice_id' => $invoice->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // Update mail status to failed
                $invoice->update(['mail_status' => 'failed']);

                throw $e;
            }
        });
    }

    /**
     * Generate PDF and store in tenant storage
     */
    protected function generatePdf(Invoice $invoice): string
    {
        $tenant = $this->tenantContext->getTenant();
        $invoice->load('lineItems', 'contact', 'tenant');

        // Generate PDF
        $pdf = Pdf::loadView('tenant.invoices.pdf', compact('invoice', 'tenant'));

        // Store in tenant-specific directory
        $directory = "tenants/{$tenant->id}/invoices";
        $filename = "invoice-{$invoice->invoice_number}-".now()->format('YmdHis').'.pdf';
        $path = "{$directory}/{$filename}";

        // Ensure directory exists
        Storage::disk('local')->makeDirectory($directory);

        // Save PDF
        Storage::disk('local')->put($path, $pdf->output());

        return $path;
    }

    /**
     * Generate secure payment token
     */
    protected function generatePaymentToken(): string
    {
        return Str::random(64);
    }

    /**
     * Send invoice email with PDF attachment
     */
    protected function sendInvoiceEmail(Invoice $invoice, string $pdfPath, string $paymentToken): array
    {
        $tenant = $this->tenantContext->getTenant();
        $contact = $invoice->contact;

        if (! $contact->email) {
            Log::warning('Invoice contact has no email', [
                'invoice_id' => $invoice->id,
                'contact_id' => $contact->id,
            ]);

            return ['status' => 'failed', 'message' => 'Contact has no email address'];
        }

        // Generate payment URL using the provided token
        $paymentUrl = route('invoice.pay', [
            'invoice' => $invoice->id,
            'token' => $paymentToken,
        ]);

        // Render email template
        $html = view('emails.invoice.send', [
            'invoice' => $invoice,
            'tenant' => $tenant,
            'contact' => $contact,
            'paymentUrl' => $paymentUrl,
        ])->render();

        $subject = "Invoice {$invoice->invoice_number} from {$tenant->name}";

        // Get full path to PDF for attachment
        $fullPdfPath = Storage::disk('local')->path($pdfPath);

        // Send email
        $sent = $this->mailer->send([
            'to' => $contact->email,
            'subject' => $subject,
            'html' => $html,
            'from_name' => $tenant->name,
            'attachments' => [$fullPdfPath],
        ]);

        return [
            'status' => $sent ? 'sent' : 'failed',
            'message_id' => $sent ? 'msg-'.Str::random(16) : null,
        ];
    }

    /**
     * Get payment URL for invoice
     */
    public function getPaymentUrl(Invoice $invoice): ?string
    {
        if (! $invoice->payment_token) {
            return null;
        }

        return route('invoice.pay', [
            'invoice' => $invoice->id,
            'token' => $invoice->payment_token,
        ]);
    }
}
