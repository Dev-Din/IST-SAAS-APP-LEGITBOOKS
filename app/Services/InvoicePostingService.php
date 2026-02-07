<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use Illuminate\Support\Facades\DB;

class InvoicePostingService
{
    public function __construct(
        protected TenantContext $tenantContext
    ) {}

    public function postInvoice(Invoice $invoice): JournalEntry
    {
        $tenant = $this->tenantContext->getTenant();

        return DB::transaction(function () use ($invoice, $tenant) {
            // Get AR account
            $arAccount = \App\Models\ChartOfAccount::where('tenant_id', $tenant->id)
                ->where('code', '1200')
                ->first();

            if (! $arAccount) {
                // Auto-create Accounts Receivable for tenants that are missing it
                $arAccount = \App\Models\ChartOfAccount::create([
                    'tenant_id' => $tenant->id,
                    'code' => '1200',
                    'name' => 'Accounts Receivable',
                    'type' => 'asset',
                    'category' => 'current_asset',
                    'is_active' => true,
                ]);
            }

            // Create journal entry
            $entryNumber = 'JE-'.date('Ymd').'-'.str_pad(JournalEntry::where('tenant_id', $tenant->id)->count() + 1, 4, '0', STR_PAD_LEFT);

            $journalEntry = JournalEntry::create([
                'tenant_id' => $tenant->id,
                'entry_number' => $entryNumber,
                'entry_date' => $invoice->invoice_date,
                'reference_type' => Invoice::class,
                'reference_id' => $invoice->id,
                'description' => "Invoice {$invoice->invoice_number}",
                'is_posted' => true,
            ]);

            // Debit AR
            JournalLine::create([
                'journal_entry_id' => $journalEntry->id,
                'chart_of_account_id' => $arAccount->id,
                'type' => 'debit',
                'amount' => $invoice->total,
                'description' => "Invoice {$invoice->invoice_number}",
            ]);

            // Credit revenue accounts per line item (use subtotal, not line_total which includes tax)
            foreach ($invoice->lineItems as $lineItem) {
                $salesAccount = $lineItem->salesAccount ??
                    \App\Models\ChartOfAccount::where('tenant_id', $tenant->id)
                        ->where('code', '4100')
                        ->first();

                if (! $salesAccount) {
                    // Auto-create Sales Revenue for tenants that are missing it
                    $salesAccount = \App\Models\ChartOfAccount::create([
                        'tenant_id' => $tenant->id,
                        'code' => '4100',
                        'name' => 'Sales Revenue',
                        'type' => 'revenue',
                        'category' => 'operating_revenue',
                        'is_active' => true,
                    ]);
                }

                if ($salesAccount) {
                    // Calculate line subtotal (quantity * unit_price) without tax
                    $lineSubtotal = $lineItem->quantity * $lineItem->unit_price;

                    JournalLine::create([
                        'journal_entry_id' => $journalEntry->id,
                        'chart_of_account_id' => $salesAccount->id,
                        'type' => 'credit',
                        'amount' => $lineSubtotal,
                        'description' => $lineItem->description,
                    ]);
                }
            }

            // Credit tax liability if applicable
            if ($invoice->tax_amount > 0) {
                $taxAccount = \App\Models\ChartOfAccount::where('tenant_id', $tenant->id)
                    ->where('code', '2200')
                    ->first();

                if (! $taxAccount) {
                    // Auto-create Tax Payable for tenants that are missing it
                    $taxAccount = \App\Models\ChartOfAccount::create([
                        'tenant_id' => $tenant->id,
                        'code' => '2200',
                        'name' => 'Tax Payable',
                        'type' => 'liability',
                        'category' => 'current_liability',
                        'is_active' => true,
                    ]);
                }

                JournalLine::create([
                    'journal_entry_id' => $journalEntry->id,
                    'chart_of_account_id' => $taxAccount->id,
                    'type' => 'credit',
                    'amount' => $invoice->tax_amount,
                    'description' => "Tax for Invoice {$invoice->invoice_number}",
                ]);
            }

            $journalEntry->calculateTotals();
            $journalEntry->save();

            if (! $journalEntry->isBalanced()) {
                throw new \Exception('Journal entry is not balanced');
            }

            return $journalEntry;
        });
    }
}
