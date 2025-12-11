@extends('layouts.tenant')

@section('title', 'Bill ' . $bill->bill_number)

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="py-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Bill {{ $bill->bill_number }}</h1>
            <div class="flex space-x-3">
                @perm('manage_bills')
                @if($bill->status === 'draft')
                <form method="POST" action="{{ route('tenant.bills.mark-received', $bill) }}" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                        Mark as Received
                    </button>
                </form>
                @endif
                @if($bill->status !== 'paid' && $bill->status !== 'cancelled')
                <a href="{{ route('tenant.bills.edit', $bill) }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white" style="background-color: var(--brand-primary);">
                    Edit
                </a>
                @endif
                @endperm
                <a href="{{ route('tenant.bills.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    Back to List
                </a>
            </div>
        </div>

        @anyperm(['manage_bills', 'view_bills'])
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Bill Details</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Status: 
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                @if($bill->status === 'paid') bg-green-100 text-green-800
                                @elseif($bill->status === 'received') bg-blue-100 text-blue-800
                                @elseif($bill->status === 'overdue') bg-red-100 text-red-800
                                @elseif($bill->status === 'draft') bg-gray-100 text-gray-800
                                @else bg-yellow-100 text-yellow-800
                                @endif">
                                {{ ucfirst($bill->status) }}
                            </span>
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-2xl font-bold text-gray-900">KES {{ number_format($bill->total, 2) }}</p>
                        @if($bill->getOutstandingAmount() > 0)
                        <p class="text-sm text-orange-600 mt-1">Outstanding: KES {{ number_format($bill->getOutstandingAmount(), 2) }}</p>
                        @endif
                    </div>
                </div>
            </div>
            <div class="px-4 py-5 sm:p-6">
                <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Bill From</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            {{ $bill->contact->name }}<br>
                            @if($bill->contact->email){{ $bill->contact->email }}<br>@endif
                            @if($bill->contact->address){{ $bill->contact->address }}@endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Bill Date</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $bill->bill_date->format('d/m/Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Due Date</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $bill->due_date ? $bill->due_date->format('d/m/Y') : 'N/A' }}</dd>
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
                            @foreach($bill->lineItems as $item)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $item->description }}
                                    @if($item->expenseAccount)
                                    <div class="text-xs text-gray-400 mt-1">{{ $item->expenseAccount->name }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $item->quantity }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">KES {{ number_format($item->unit_price, 2) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $item->tax_rate }}%</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">KES {{ number_format($item->line_total, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-right text-sm font-medium text-gray-900">Subtotal:</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">KES {{ number_format($bill->subtotal, 2) }}</td>
                            </tr>
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-right text-sm font-medium text-gray-900">Tax:</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">KES {{ number_format($bill->tax_amount, 2) }}</td>
                            </tr>
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-right text-sm font-bold text-gray-900">Total:</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">KES {{ number_format($bill->total, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                @if($bill->paymentAllocations->count() > 0)
                <div class="mt-8">
                    <h4 class="text-lg font-medium text-gray-900 mb-4">Payments</h4>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($bill->paymentAllocations as $allocation)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $allocation->payment->payment_date->format('d/m/Y') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">KES {{ number_format($allocation->amount, 2) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 capitalize">{{ str_replace('_', ' ', $allocation->payment->payment_method) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        @if($allocation->payment->transaction_status === 'completed') bg-green-100 text-green-800
                                        @elseif($allocation->payment->transaction_status === 'pending') bg-yellow-100 text-yellow-800
                                        @else bg-red-100 text-red-800
                                        @endif">
                                        {{ ucfirst($allocation->payment->transaction_status) }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif

                @if($bill->notes)
                <div class="mt-6">
                    <h4 class="text-lg font-medium text-gray-900 mb-2">Notes</h4>
                    <p class="text-sm text-gray-500">{{ $bill->notes }}</p>
                </div>
                @endif
            </div>
        </div>
        @else
        <div class="bg-white shadow overflow-hidden sm:rounded-lg p-8 text-center text-gray-500">
            You do not have permission to view this bill.
        </div>
        @endanyperm
    </div>
</div>
@endsection
