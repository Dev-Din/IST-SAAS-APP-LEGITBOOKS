<div class="bg-green-600 px-6 py-5">
    <h1 class="text-2xl font-bold text-white">Payment Successful!</h1>
    <p class="text-green-100 text-sm mt-1">Your payment has been confirmed.</p>
</div>
<div class="px-6 py-8 text-center">
    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <svg class="w-8 h-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
    </div>
    <h2 class="text-xl font-semibold text-gray-900 mb-2">Invoice {{ $invoice->invoice_number }} is Paid</h2>
    <p class="text-gray-600 mb-4">Thank you for your payment.</p>
    <div class="bg-gray-50 rounded-lg p-4 mb-6 text-left">
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <p class="text-gray-500">Invoice Number:</p>
                <p class="font-semibold text-gray-900">{{ $invoice->invoice_number }}</p>
            </div>
            <div>
                <p class="text-gray-500">Amount Paid:</p>
                <p class="font-semibold text-gray-900">KES {{ number_format($invoice->total, 2) }}</p>
            </div>
            <div>
                <p class="text-gray-500">Date:</p>
                <p class="font-semibold text-gray-900">{{ $invoice->invoice_date->format('d/m/Y') }}</p>
            </div>
            <div>
                <p class="text-gray-500">Status:</p>
                <p class="font-semibold text-green-600">Paid</p>
            </div>
        </div>
    </div>
    @if($invoice->paymentAllocations->count() > 0)
    <div class="mb-6">
        <h3 class="text-sm font-medium text-gray-700 mb-2">Payment Details</h3>
        <div class="space-y-2">
            @foreach($invoice->paymentAllocations as $allocation)
            <div class="bg-gray-50 rounded p-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600">Payment Date:</span>
                    <span class="font-medium">{{ $allocation->payment->payment_date->format('d/m/Y') }} {{ $allocation->payment->payment_date->format('H:i:s') }}</span>
                </div>
                <div class="flex justify-between mt-1">
                    <span class="text-gray-600">Amount:</span>
                    <span class="font-medium">KES {{ number_format($allocation->amount, 2) }}</span>
                </div>
                @if($allocation->payment->mpesa_receipt)
                <div class="flex justify-between mt-1">
                    <span class="text-gray-600">M-Pesa Receipt:</span>
                    <span class="font-medium">{{ $allocation->payment->mpesa_receipt }}</span>
                </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif
    <div class="flex flex-wrap gap-3 justify-center">
        <a href="{{ route('invoice.pay.receipt.pdf', [$invoice->id, $invoice->payment_token]) }}" target="_blank" class="inline-flex items-center bg-green-600 text-white px-6 py-2 rounded-md hover:bg-green-700">
            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Download Receipt (PDF)
        </a>
        <a href="{{ url('/') }}" class="inline-block bg-gray-800 text-white px-6 py-2 rounded-md hover:bg-gray-900">
            Return Home
        </a>
    </div>
</div>
