@extends('layouts.tenant')

@section('title', 'Invoice ' . $invoice->invoice_number)

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="py-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Invoice {{ $invoice->invoice_number }}</h1>
            <div class="flex space-x-3">
                @anyperm(['manage_invoices', 'view_invoices'])
                <a href="{{ route('tenant.invoices.pdf', $invoice) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    Download PDF
                </a>
                @endanyperm
                @perm('manage_invoices')
                @if($invoice->status !== 'paid' && $invoice->status !== 'cancelled')
                    @if($invoice->status === 'sent')
                        <span class="inline-flex items-center px-4 py-2 border border-green-300 rounded-md shadow-sm text-sm font-medium text-green-700 bg-green-50">
                            Sent on {{ $invoice->sent_at ? $invoice->sent_at->format('d/m/Y H:i') : 'N/A' }}
                        </span>
                        <form method="POST" action="{{ route('tenant.invoices.send', $invoice) }}" class="inline" onsubmit="return confirm('Are you sure you want to resend this invoice to {{ $invoice->contact->email ?? $invoice->contact->name }}?');">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                Resend
                            </button>
                        </form>
                        @if($invoice->mail_status === 'failed')
                            <span class="inline-flex items-center px-3 py-2 border border-red-300 rounded-md shadow-sm text-xs font-medium text-red-700 bg-red-50">
                                Email Failed
                            </span>
                        @endif
                    @else
                        <form method="POST" action="{{ route('tenant.invoices.send', $invoice) }}" class="inline" onsubmit="return confirm('Are you sure you want to send this invoice to {{ $invoice->contact->email ?? $invoice->contact->name }}?');">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                Send Invoice
                            </button>
                        </form>
                    @endif
                @endif
                <a href="{{ route('tenant.invoices.edit', $invoice) }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white" style="background-color: var(--brand-primary);">
                    Edit
                </a>
                @endperm
            </div>
        </div>

        @anyperm(['manage_invoices', 'view_invoices'])
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Invoice Details</h3>
                    </div>
                    <div class="text-right">
                        <p class="text-2xl font-bold text-gray-900">{{ number_format($invoice->total, 2) }}</p>
                    </div>
                </div>
            </div>
            <div class="px-4 py-5 sm:p-6">
                <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Billed To</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            {{ $invoice->contact->name }}<br>
                            @if($invoice->contact->email){{ $invoice->contact->email }}<br>@endif
                            @if($invoice->contact->address){{ $invoice->contact->address }}@endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Invoice Date</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $invoice->invoice_date->format('d/m/Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Due Date</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : 'N/A' }}</dd>
                    </div>
                </dl>

                <div class="mt-8">
                    <h4 class="text-lg font-medium text-gray-900 mb-4">Line Items</h4>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tax</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($invoice->lineItems as $item)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $item->description }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $item->quantity }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ number_format($item->unit_price, 2) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $item->tax_rate }}%</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ number_format($item->line_total, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-right text-sm font-medium text-gray-900">Subtotal:</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ number_format($invoice->subtotal, 2) }}</td>
                            </tr>
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-right text-sm font-medium text-gray-900">Tax:</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ number_format($invoice->tax_amount, 2) }}</td>
                            </tr>
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-right text-sm font-bold text-gray-900">Total:</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">{{ number_format($invoice->total, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                @if($invoice->notes)
                <div class="mt-6">
                    <h4 class="text-lg font-medium text-gray-900 mb-2">Notes</h4>
                    <p class="text-sm text-gray-500">{{ $invoice->notes }}</p>
                </div>
                @endif
            </div>
        </div>
        @else
        <div class="bg-white shadow overflow-hidden sm:rounded-lg p-8 text-center text-gray-500">
            You do not have permission to view this invoice.
        </div>
        @endanyperm
    </div>
</div>
@endsection

