@extends('layouts.tenant')

@section('title', 'Payment #' . $payment->id)

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <a href="{{ route('tenant.payments.index') }}" class="text-sm text-gray-600 hover:text-gray-900 mb-2 inline-block">&larr; Back to Payments</a>
            <h1 class="text-2xl font-bold text-gray-900">Payment {{ $payment->payment_number ?? '#' . $payment->id }}</h1>
            <p class="text-sm text-gray-600 mt-1">Payment details</p>
        </div>
    </div>

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Payment Number</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $payment->payment_number ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Amount</dt>
                    <dd class="mt-1 text-sm font-semibold text-gray-900">KSh {{ number_format($payment->amount, 2) }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Payment Method</dt>
                    <dd class="mt-1 text-sm text-gray-900 capitalize">{{ $payment->payment_method ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Status</dt>
                    <dd class="mt-1">
                        @if($payment->transaction_status === 'completed')
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Completed</span>
                        @elseif($payment->transaction_status === 'pending')
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>
                        @elseif($payment->transaction_status === 'failed')
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Failed</span>
                        @else
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">{{ ucfirst($payment->transaction_status ?? 'Unknown') }}</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Date</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        @if($payment->payment_date)
                            {{ $payment->payment_date->format('d/m/Y') }} {{ $payment->created_at->format('H:i:s') }}
                        @else
                            {{ $payment->created_at->format('d/m/Y H:i:s') }}
                        @endif
                    </dd>
                </div>
                @if($payment->reference)
                <div>
                    <dt class="text-sm font-medium text-gray-500">Reference</dt>
                    <dd class="mt-1 text-sm font-mono text-gray-900">{{ $payment->reference }}</dd>
                </div>
                @endif
                @if($payment->mpesa_receipt)
                <div>
                    <dt class="text-sm font-medium text-gray-500">M-Pesa Receipt</dt>
                    <dd class="mt-1 text-sm font-mono text-gray-900">{{ $payment->mpesa_receipt }}</dd>
                </div>
                @endif
                @if($payment->invoice)
                <div>
                    <dt class="text-sm font-medium text-gray-500">Invoice</dt>
                    <dd class="mt-1">
                        <a href="{{ route('tenant.invoices.show', $payment->invoice->id) }}" class="text-blue-600 hover:text-blue-900">{{ $payment->invoice->invoice_number }}</a>
                    </dd>
                </div>
                @endif
                @if($payment->notes)
                <div class="sm:col-span-2">
                    <dt class="text-sm font-medium text-gray-500">Notes</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $payment->notes }}</dd>
                </div>
                @endif
            </dl>
        </div>
    </div>
</div>
@endsection
