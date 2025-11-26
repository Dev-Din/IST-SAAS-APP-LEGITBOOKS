@extends('layouts.tenant')

@section('title', 'M-Pesa Payment Confirmations')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">M-Pesa Payment Confirmations</h1>
            <p class="text-sm text-gray-600 mt-1">All confirmed M-Pesa payments and receipts</p>
        </div>
        <button onclick="validatePendingPayments()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium">
            Validate Pending
        </button>
    </div>

    @if(session('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded" role="alert">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded" role="alert">
            {{ session('error') }}
        </div>
    @endif

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-600">Total Payments</div>
            <div class="text-2xl font-bold text-gray-900">{{ $payments->total() }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-600">Completed</div>
            <div class="text-2xl font-bold text-green-600">
                {{ $payments->where('transaction_status', 'completed')->count() }}
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-600">Pending</div>
            <div class="text-2xl font-bold text-yellow-600">
                {{ $payments->where('transaction_status', 'pending')->count() }}
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-600">Total Amount</div>
            <div class="text-2xl font-bold text-gray-900">
                KSh {{ number_format($payments->sum('amount'), 2) }}
            </div>
        </div>
    </div>

    <!-- Payments Table -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Receipt Number</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($payments as $payment)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            #{{ $payment->id }}
                            @if($payment->payment_number)
                                <br><span class="text-xs text-gray-500">{{ $payment->payment_number }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            @if($payment->mpesa_receipt)
                                <span class="font-mono font-semibold">{{ $payment->mpesa_receipt }}</span>
                            @else
                                <span class="text-gray-400">N/A</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <span class="font-semibold">KSh {{ number_format($payment->amount, 2) }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $payment->phone ?? 'N/A' }}
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
                                <br><span class="text-xs">{{ $payment->created_at->format('H:i:s') }}</span>
                            @else
                                {{ $payment->created_at->format('d/m/Y H:i:s') }}
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            @if($payment->reference)
                                <span class="font-mono text-xs">{{ $payment->reference }}</span>
                            @else
                                <span class="text-gray-400">N/A</span>
                            @endif
                            @if($payment->invoice)
                                <br><span class="text-xs text-blue-600">Invoice: {{ $payment->invoice->invoice_number }}</span>
                            @endif
                            @if($payment->subscription)
                                <br><span class="text-xs text-purple-600">Subscription: {{ $payment->subscription->plan }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="{{ route('tenant.payments.receipts.show', $payment->id) }}" 
                               class="text-blue-600 hover:text-blue-900 mr-3">View</a>
                            @if($payment->mpesa_receipt)
                                <button onclick="validateReceipt({{ $payment->id }})" 
                                        class="text-green-600 hover:text-green-900">Validate</button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">
                            No M-Pesa payments found
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

<script>
function validateReceipt(paymentId) {
    if (!confirm('Validate this payment receipt with M-Pesa?')) {
        return;
    }

    fetch(`{{ url('/app/payments/receipts') }}/${paymentId}/validate`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.valid) {
            alert('✅ Payment receipt validated successfully!\n\nReceipt: ' + data.receipt + '\n' + data.message);
        } else {
            alert('❌ Validation failed: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while validating the receipt');
    });
}

function validatePendingPayments() {
    if (!confirm('Validate all pending M-Pesa payments? This may take a few moments.')) {
        return;
    }

    fetch('{{ route("tenant.payments.receipts.validate-pending") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`✅ Validation complete!\n\nProcessed: ${data.count} payment(s)\n\nPlease refresh the page to see updated statuses.`);
            window.location.reload();
        } else {
            alert('❌ Validation failed: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while validating payments');
    });
}
</script>
@endsection

