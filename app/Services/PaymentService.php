<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Services\TenantContext;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        protected TenantContext $tenantContext
    ) {}

    public function processPayment(Payment $payment, array $allocations = []): JournalEntry
    {
        $tenant = $this->tenantContext->getTenant();

        return DB::transaction(function () use ($payment, $allocations, $tenant) {
            // Get bank/cash account
            $account = $payment->account;
            $coa = $account->chartOfAccount;

            // Create journal entry
            $entryNumber = 'JE-' . date('Ymd') . '-' . str_pad(JournalEntry::where('tenant_id', $tenant->id)->count() + 1, 4, '0', STR_PAD_LEFT);
            
            $journalEntry = JournalEntry::create([
                'tenant_id' => $tenant->id,
                'entry_number' => $entryNumber,
                'entry_date' => $payment->payment_date,
                'reference_type' => Payment::class,
                'reference_id' => $payment->id,
                'description' => "Payment {$payment->payment_number}",
                'is_posted' => true,
            ]);

            // Debit bank/cash account
            JournalLine::create([
                'journal_entry_id' => $journalEntry->id,
                'chart_of_account_id' => $coa->id,
                'type' => 'debit',
                'amount' => $payment->amount,
                'description' => "Payment {$payment->payment_number}",
            ]);

            // Credit AR for each allocation
            $arAccount = \App\Models\ChartOfAccount::where('tenant_id', $tenant->id)
                ->where('code', '1200')
                ->first();

            if (!$arAccount) {
                throw new \Exception('Accounts Receivable account not found');
            }

            $totalAllocated = 0;
            foreach ($allocations as $allocation) {
                $allocationModel = PaymentAllocation::create([
                    'payment_id' => $payment->id,
                    'invoice_id' => $allocation['invoice_id'],
                    'amount' => $allocation['amount'],
                ]);

                JournalLine::create([
                    'journal_entry_id' => $journalEntry->id,
                    'chart_of_account_id' => $arAccount->id,
                    'type' => 'credit',
                    'amount' => $allocation['amount'],
                    'description' => "Payment allocation for Invoice {$allocationModel->invoice->invoice_number}",
                ]);

                $totalAllocated += $allocation['amount'];
            }

            // Handle overpayment/unapplied credits
            if ($payment->amount > $totalAllocated) {
                $overpayment = $payment->amount - $totalAllocated;
                // Credit a liability account for unapplied credits
                $unappliedAccount = \App\Models\ChartOfAccount::where('tenant_id', $tenant->id)
                    ->where('code', '2200')
                    ->first();

                if ($unappliedAccount) {
                    JournalLine::create([
                        'journal_entry_id' => $journalEntry->id,
                        'chart_of_account_id' => $unappliedAccount->id,
                        'type' => 'credit',
                        'amount' => $overpayment,
                        'description' => "Unapplied payment amount",
                    ]);
                }
            }

            $journalEntry->calculateTotals();
            $journalEntry->save();

            if (!$journalEntry->isBalanced()) {
                throw new \Exception('Journal entry is not balanced');
            }

            return $journalEntry;
        });
    }
}

