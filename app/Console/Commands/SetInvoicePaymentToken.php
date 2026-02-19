<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SetInvoicePaymentToken extends Command
{
    protected $signature = 'invoice:set-payment-token
                            {invoice_id : The invoice ID}
                            {--token= : Optional token to set; if omitted, a new token is generated}';

    protected $description = 'Set or regenerate payment_token for an invoice so the public payment link (/pay/{id}/{token}) works';

    public function handle(): int
    {
        $invoiceId = (int) $this->argument('invoice_id');
        $token = $this->option('token');

        $invoice = Invoice::withoutGlobalScope('tenant')->find($invoiceId);

        if (! $invoice) {
            $this->error("Invoice with ID {$invoiceId} not found.");

            return self::FAILURE;
        }

        if (! $token) {
            $token = Str::random(64);
            $this->info('Generated new payment token.');
        }

        $invoice->payment_token = $token;
        $invoice->save();

        $url = url("/pay/{$invoice->id}/{$invoice->payment_token}");
        $this->info("Payment token set for invoice ID {$invoice->id} ({$invoice->invoice_number}).");
        $this->line('Payment URL: '.$url);

        return self::SUCCESS;
    }
}
