@extends('layouts.tenant')

@section('title', 'Reports')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="py-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Reports</h1>
        </div>

        <!-- Date Range Filter -->
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <form method="GET" action="{{ route('tenant.reports.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                        <input type="date" name="date_from" value="{{ $dateFrom }}" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                        <input type="date" name="date_to" value="{{ $dateTo }}" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>
                <div class="flex space-x-3">
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        Apply Filters
                    </button>
                    <a href="{{ route('tenant.reports.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Revenue Summary -->
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Revenue Summary</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-blue-50 p-4 rounded-lg">
                    <div class="text-sm text-blue-600 font-medium">Total Revenue</div>
                    <div class="text-2xl font-bold text-blue-900">KES {{ number_format($revenueSummary['total_revenue'], 2) }}</div>
                </div>
                <div class="bg-green-50 p-4 rounded-lg">
                    <div class="text-sm text-green-600 font-medium">Paid Revenue</div>
                    <div class="text-2xl font-bold text-green-900">KES {{ number_format($revenueSummary['paid_revenue'], 2) }}</div>
                </div>
                <div class="bg-yellow-50 p-4 rounded-lg">
                    <div class="text-sm text-yellow-600 font-medium">Outstanding Revenue</div>
                    <div class="text-2xl font-bold text-yellow-900">KES {{ number_format($revenueSummary['outstanding_revenue'], 2) }}</div>
                </div>
            </div>
            @if(!empty($revenueSummary['revenue_trend']))
            <div>
                <h3 class="text-sm font-medium text-gray-700 mb-2">Revenue Trend</h3>
                <div class="space-y-1 max-h-64 overflow-y-auto">
                    @foreach($revenueSummary['revenue_trend'] as $trend)
                    <div class="flex justify-between text-sm py-1 border-b border-gray-100">
                        <span class="text-gray-600">{{ $trend['date'] }}</span>
                        <span class="font-medium">KES {{ number_format($trend['revenue'], 2) }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <!-- Payment Collection -->
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Payment Collection</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-green-50 p-4 rounded-lg">
                    <div class="text-sm text-green-600 font-medium">Total Collected</div>
                    <div class="text-2xl font-bold text-green-900">KES {{ number_format($paymentCollection['total_collected'], 2) }}</div>
                </div>
                <div class="bg-red-50 p-4 rounded-lg">
                    <div class="text-sm text-red-600 font-medium">Outstanding</div>
                    <div class="text-2xl font-bold text-red-900">KES {{ number_format($paymentCollection['outstanding'], 2) }}</div>
                </div>
                <div class="bg-blue-50 p-4 rounded-lg">
                    <div class="text-sm text-blue-600 font-medium">Collection Rate</div>
                    <div class="text-2xl font-bold text-blue-900">{{ number_format($paymentCollection['collection_rate'], 2) }}%</div>
                </div>
            </div>
            
            @if(!empty($paymentCollection['by_status']))
            <div class="mb-4">
                <h3 class="text-sm font-medium text-gray-700 mb-2">Payments by Status</h3>
                <div class="space-y-2">
                    @foreach($paymentCollection['by_status'] as $status => $data)
                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                        <span class="text-gray-600 capitalize">{{ str_replace('_', ' ', $status) }}</span>
                        <div class="text-right">
                            <div class="font-medium">KES {{ number_format($data['total'], 2) }}</div>
                            <div class="text-xs text-gray-500">{{ $data['count'] }} payment(s)</div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            @if(!empty($paymentCollection['by_method']))
            <div>
                <h3 class="text-sm font-medium text-gray-700 mb-2">Payments by Method</h3>
                <div class="space-y-2">
                    @foreach($paymentCollection['by_method'] as $method => $data)
                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                        <span class="text-gray-600 capitalize">{{ str_replace('_', ' ', $method) }}</span>
                        <div class="text-right">
                            <div class="font-medium">KES {{ number_format($data['total'], 2) }}</div>
                            <div class="text-xs text-gray-500">{{ $data['count'] }} payment(s)</div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <!-- Invoice Summary -->
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Invoice Summary</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-purple-50 p-4 rounded-lg">
                    <div class="text-sm text-purple-600 font-medium">Total Invoices</div>
                    <div class="text-2xl font-bold text-purple-900">{{ number_format($invoiceSummary['total_invoices']) }}</div>
                </div>
                <div class="bg-indigo-50 p-4 rounded-lg">
                    <div class="text-sm text-indigo-600 font-medium">Total Invoiced</div>
                    <div class="text-2xl font-bold text-indigo-900">KES {{ number_format($invoiceSummary['total_invoiced'], 2) }}</div>
                </div>
                <div class="bg-pink-50 p-4 rounded-lg">
                    <div class="text-sm text-pink-600 font-medium">Average Invoice</div>
                    <div class="text-2xl font-bold text-pink-900">KES {{ number_format($invoiceSummary['average_invoice'], 2) }}</div>
                </div>
            </div>
            
            @if(!empty($invoiceSummary['by_status']))
            <div>
                <h3 class="text-sm font-medium text-gray-700 mb-2">Invoices by Status</h3>
                <div class="space-y-2">
                    @foreach($invoiceSummary['by_status'] as $status => $data)
                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                        <span class="text-gray-600 capitalize">{{ str_replace('_', ' ', $status) }}</span>
                        <div class="text-right">
                            <div class="font-medium">KES {{ number_format($data['total'], 2) }}</div>
                            <div class="text-xs text-gray-500">{{ $data['count'] }} invoice(s)</div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
