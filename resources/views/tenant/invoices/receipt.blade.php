@extends('layouts.tenant')

@section('title', 'Receipt - Invoice ' . $invoice->invoice_number)

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="py-6">
        <h1 class="text-3xl font-bold text-gray-900 mb-6">Payment Receipt</h1>

        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 bg-green-50 border-b border-green-200">
                <h2 class="text-xl font-semibold text-green-900">Payment Confirmed</h2>
                <p class="text-sm text-green-700 mt-1">Invoice {{ $invoice->invoice_number }} has been paid in full.</p>
            </div>

            <div class="px-6 py-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 mb-2">Invoice Details</h3>
                        <p class="text-lg font-semibold text-gray-900">{{ $invoice->invoice_number }}</p>
                        <p class="text-sm text-gray-600">Date: {{ $invoice->invoice_date->format('d/m/Y') }}</p>
                        @if($invoice->due_date)
                        <p class="text-sm text-gray-600">Due: {{ $invoice->due_date->format('d/m/Y') }}</p>
                        @endif
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 mb-2">Payment Summary</h3>
                        <p class="text-lg font-semibold text-gray-900">KES {{ number_format($invoice->total, 2) }}</p>
                        <p class="text-sm text-gray-600">Status: <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-semibold">Paid</span></p>
                    </div>
                </div>

                @if($invoice->paymentAllocations->count() > 0)
                <div class="mb-6">
                    <h3 class="text-sm font-medium text-gray-900 mb-3">Payment Allocations</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Payment Number</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Method</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($invoice->paymentAllocations as $allocation)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ $allocation->payment->payment_number }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $allocation->payment->payment_date->format('d/m/Y') }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ ucfirst(str_replace('_', ' ', $allocation->payment->payment_method)) }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900 text-right">KES {{ number_format($allocation->amount, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td colspan="3" class="px-4 py-3 text-sm font-semibold text-gray-900 text-right">Total Paid:</td>
                                    <td class="px-4 py-3 text-sm font-semibold text-gray-900 text-right">KES {{ number_format($invoice->total, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                @endif

                <div class="flex justify-end space-x-3 mt-6">
                    <a href="{{ route('tenant.invoices.show', $invoice) }}" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        View Invoice
                    </a>
                    <button onclick="window.print()" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white" style="background-color: var(--brand-primary);">
                        Print Receipt
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

