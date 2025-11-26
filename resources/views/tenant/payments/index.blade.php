@extends('layouts.tenant')

@section('title', 'Payments')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Payments</h1>
            <p class="text-sm text-gray-600 mt-1">All payment transactions</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('tenant.payments.receipts') }}" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium">
                M-Pesa Receipts
            </a>
            <a href="{{ route('tenant.payments.json.fetch') }}" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 text-sm font-medium" target="_blank">
                Export JSON
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded" role="alert">
            {{ session('success') }}
        </div>
    @endif

    @if(session('info'))
        <div class="mb-4 bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded" role="alert">
            {{ session('info') }}
        </div>
    @endif

    <!-- Payments Table -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($payments as $payment)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">#{{ $payment->id }}</div>
                            @if($payment->payment_number)
                                <div class="text-xs text-gray-500">{{ $payment->payment_number }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <span class="font-semibold">KSh {{ number_format($payment->amount, 2) }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <span class="capitalize">{{ $payment->payment_method ?? 'N/A' }}</span>
                            @if($payment->mpesa_receipt)
                                <div class="text-xs text-gray-500 font-mono">{{ $payment->mpesa_receipt }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($payment->transaction_status === 'completed')
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Completed
                                </span>
                            @elseif($payment->transaction_status === 'pending')
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    Pending
                                </span>
                            @elseif($payment->transaction_status === 'failed')
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                    Failed
                                </span>
                            @else
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                    {{ ucfirst($payment->transaction_status ?? 'Unknown') }}
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            @if($payment->payment_date)
                                {{ $payment->payment_date->format('d/m/Y') }}
                            @else
                                {{ $payment->created_at->format('d/m/Y') }}
                            @endif
                            <div class="text-xs">{{ $payment->created_at->format('H:i:s') }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            @if($payment->invoice)
                                <a href="{{ route('tenant.invoices.show', $payment->invoice->id) }}" class="text-blue-600 hover:text-blue-900">
                                    Invoice: {{ $payment->invoice->invoice_number }}
                                </a>
                            @elseif($payment->subscription)
                                <span class="text-purple-600">Subscription: {{ $payment->subscription->plan }}</span>
                            @elseif($payment->reference)
                                <span class="font-mono text-xs">{{ $payment->reference }}</span>
                            @else
                                <span class="text-gray-400">N/A</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="{{ route('tenant.payments.show', $payment->id) }}" class="text-blue-600 hover:text-blue-900">View</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                            No payments found
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($payments->hasPages())
        <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
            {{ $payments->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

