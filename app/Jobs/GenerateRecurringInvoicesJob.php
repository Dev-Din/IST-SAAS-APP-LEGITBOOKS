<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\RecurringTemplate;
use App\Services\InvoiceNumberService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class GenerateRecurringInvoicesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(InvoiceNumberService $numberService): void
    {
        RecurringTemplate::where('is_active', true)
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', now())
            ->each(function (RecurringTemplate $template) use ($numberService) {
                DB::transaction(function () use ($template, $numberService) {
                    $data = $template->invoice_template ?? [];

                    $invoice = Invoice::create([
                        'tenant_id' => $template->tenant_id,
                        'invoice_number' => $numberService->generate($template->tenant_id),
                        'contact_id' => $template->contact_id,
                        'invoice_date' => now()->toDateString(),
                        'due_date' => now()->addDays(14)->toDateString(),
                        'status' => 'draft',
                        'subtotal' => $data['subtotal'] ?? 0,
                        'tax_amount' => $data['tax_amount'] ?? 0,
                        'total' => $data['total'] ?? 0,
                        'notes' => $data['notes'] ?? null,
                    ]);

                    foreach ($data['items'] ?? [] as $item) {
                        InvoiceLineItem::create([
                            'invoice_id' => $invoice->id,
                            'description' => $item['description'],
                            'quantity' => $item['quantity'],
                            'unit_price' => $item['unit_price'],
                            'tax_rate' => $item['tax_rate'] ?? 0,
                            'line_total' => $item['line_total'],
                            'sales_account_id' => $item['sales_account_id'] ?? null,
                        ]);
                    }
                });

                $template->update(['next_run_at' => $this->calculateNextRun($template)]);
            });
    }

    protected function calculateNextRun(RecurringTemplate $template): ?string
    {
        $next = Carbon::parse($template->next_run_at ?? now());

        $result = match ($template->frequency) {
            'daily' => $next->addDay(),
            'weekly' => $next->addWeek(),
            'monthly' => $next->addMonth(),
            'yearly' => $next->addYear(),
            default => $next->addMonth(),
        };

        return $result->toDateTimeString();
    }
}
