@extends('layouts.admin')

@section('title', 'Reports')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="py-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Reports</h1>
        </div>

        <!-- Date Range Filter -->
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <form method="GET" action="{{ route('admin.reports.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                        <input type="date" name="date_from" value="{{ $dateFrom }}" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                        <input type="date" name="date_to" value="{{ $dateTo }}" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Compare From (Optional)</label>
                        <input type="date" name="compare_from" value="{{ $compareFrom }}" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Compare To (Optional)</label>
                        <input type="date" name="compare_to" value="{{ $compareTo }}" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>
                <div class="flex space-x-3">
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        Apply Filters
                    </button>
                    <a href="{{ route('admin.reports.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- 1. Tenant Overview Dashboard -->
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-900">Tenant Overview</h2>
                <div class="flex space-x-2">
                    <form method="POST" action="{{ route('admin.reports.export') }}" class="inline">
                        @csrf
                        <input type="hidden" name="report" value="tenant_overview">
                        <input type="hidden" name="date_from" value="{{ $dateFrom }}">
                        <input type="hidden" name="date_to" value="{{ $dateTo }}">
                        <button type="submit" name="type" value="csv" class="px-3 py-1 text-sm bg-gray-100 text-gray-700 rounded hover:bg-gray-200">CSV</button>
                        <button type="submit" name="type" value="excel" class="px-3 py-1 text-sm bg-gray-100 text-gray-700 rounded hover:bg-gray-200">Excel</button>
                        <button type="submit" name="type" value="pdf" class="px-3 py-1 text-sm bg-gray-100 text-gray-700 rounded hover:bg-gray-200">PDF</button>
                    </form>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-blue-50 p-4 rounded-lg">
                    <div class="text-sm text-blue-600 font-medium">Total Tenants</div>
                    <div class="text-2xl font-bold text-blue-900">{{ number_format($tenantOverview['total']) }}</div>
                </div>
                <div class="bg-green-50 p-4 rounded-lg">
                    <div class="text-sm text-green-600 font-medium">Active Tenants</div>
                    <div class="text-2xl font-bold text-green-900">{{ number_format($tenantOverview['active']) }}</div>
                </div>
                <div class="bg-yellow-50 p-4 rounded-lg">
                    <div class="text-sm text-yellow-600 font-medium">Suspended</div>
                    <div class="text-2xl font-bold text-yellow-900">{{ number_format($tenantOverview['suspended']) }}</div>
                </div>
                <div class="bg-purple-50 p-4 rounded-lg">
                    <div class="text-sm text-purple-600 font-medium">Trial Tenants</div>
                    <div class="text-2xl font-bold text-purple-900">{{ number_format($tenantOverview['trial']) }}</div>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Growth Trend (Last 6 Months)</h3>
                    <div class="space-y-1">
                        @foreach($tenantOverview['growth_trend'] as $trend)
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">{{ $trend['month'] }}</span>
                            <span class="font-medium">{{ $trend['count'] }} tenants</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Monthly Comparison</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-gray-600">This Month</span>
                            <span class="font-medium">{{ $tenantOverview['new_this_month'] }} new tenants</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Last Month</span>
                            <span class="font-medium">{{ $tenantOverview['new_last_month'] }} new tenants</span>
                        </div>
                        @php
                            $growth = $tenantOverview['new_last_month'] > 0 
                                ? (($tenantOverview['new_this_month'] - $tenantOverview['new_last_month']) / $tenantOverview['new_last_month']) * 100 
                                : 0;
                        @endphp
                        <div class="flex justify-between">
                            <span class="text-gray-600">Growth</span>
                            <span class="font-medium {{ $growth >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $growth >= 0 ? '+' : '' }}{{ number_format($growth, 1) }}%
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Revenue Summary -->
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-900">Revenue Summary</h2>
                <div class="flex space-x-2">
                    <form method="POST" action="{{ route('admin.reports.export') }}" class="inline">
                        @csrf
                        <input type="hidden" name="report" value="revenue">
                        <input type="hidden" name="date_from" value="{{ $dateFrom }}">
                        <input type="hidden" name="date_to" value="{{ $dateTo }}">
                        <button type="submit" name="type" value="csv" class="px-3 py-1 text-sm bg-gray-100 text-gray-700 rounded hover:bg-gray-200">CSV</button>
                        <button type="submit" name="type" value="excel" class="px-3 py-1 text-sm bg-gray-100 text-gray-700 rounded hover:bg-gray-200">Excel</button>
                        <button type="submit" name="type" value="pdf" class="px-3 py-1 text-sm bg-gray-100 text-gray-700 rounded hover:bg-gray-200">PDF</button>
                    </form>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-green-50 p-4 rounded-lg">
                    <div class="text-sm text-green-600 font-medium">Total Revenue</div>
                    <div class="text-2xl font-bold text-green-900">KES {{ number_format($revenueSummary['total_revenue'], 2) }}</div>
                    @if($comparisonData)
                    <div class="text-xs text-gray-500 mt-1">
                        Previous: KES {{ number_format($comparisonData['revenue']['total_revenue'] ?? 0, 2) }}
                    </div>
                    @endif
                </div>
                <div class="bg-blue-50 p-4 rounded-lg">
                    <div class="text-sm text-blue-600 font-medium">Monthly Recurring Revenue (MRR)</div>
                    <div class="text-2xl font-bold text-blue-900">KES {{ number_format($revenueSummary['mrr'], 2) }}</div>
                </div>
                <div class="bg-purple-50 p-4 rounded-lg">
                    <div class="text-sm text-purple-600 font-medium">Average Revenue Per Tenant (ARPU)</div>
                    <div class="text-2xl font-bold text-purple-900">KES {{ number_format($revenueSummary['arpu'], 2) }}</div>
                </div>
                <div class="bg-indigo-50 p-4 rounded-lg">
                    <div class="text-sm text-indigo-600 font-medium">Period</div>
                    <div class="text-sm font-medium text-indigo-900">
                        {{ \Carbon\Carbon::parse($dateFrom)->format('M d') }} - {{ \Carbon\Carbon::parse($dateTo)->format('M d, Y') }}
                    </div>
                </div>
            </div>
            @if(!empty($revenueSummary['revenue_by_plan']))
            <div class="mb-4">
                <h3 class="text-sm font-medium text-gray-700 mb-2">Revenue by Plan</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    @foreach($revenueSummary['revenue_by_plan'] as $plan => $amount)
                    <div class="bg-gray-50 p-3 rounded">
                        <div class="text-xs text-gray-600">{{ ucfirst(str_replace('plan_', '', $plan)) }}</div>
                        <div class="text-lg font-semibold">KES {{ number_format($amount, 2) }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <!-- 3. Subscription Metrics -->
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-900">Subscription Metrics</h2>
                <div class="flex space-x-2">
                    <form method="POST" action="{{ route('admin.reports.export') }}" class="inline">
                        @csrf
                        <input type="hidden" name="report" value="subscription">
                        <input type="hidden" name="date_from" value="{{ $dateFrom }}">
                        <input type="hidden" name="date_to" value="{{ $dateTo }}">
                        <button type="submit" name="type" value="csv" class="px-3 py-1 text-sm bg-gray-100 text-gray-700 rounded hover:bg-gray-200">CSV</button>
                        <button type="submit" name="type" value="excel" class="px-3 py-1 text-sm bg-gray-100 text-gray-700 rounded hover:bg-gray-200">Excel</button>
                        <button type="submit" name="type" value="pdf" class="px-3 py-1 text-sm bg-gray-100 text-gray-700 rounded hover:bg-gray-200">PDF</button>
                    </form>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
                <div class="bg-blue-50 p-4 rounded-lg">
                    <div class="text-sm text-blue-600 font-medium">Total</div>
                    <div class="text-2xl font-bold text-blue-900">{{ number_format($subscriptionMetrics['total']) }}</div>
                </div>
                <div class="bg-green-50 p-4 rounded-lg">
                    <div class="text-sm text-green-600 font-medium">Active</div>
                    <div class="text-2xl font-bold text-green-900">{{ number_format($subscriptionMetrics['active']) }}</div>
                </div>
                <div class="bg-yellow-50 p-4 rounded-lg">
                    <div class="text-sm text-yellow-600 font-medium">Trial</div>
                    <div class="text-2xl font-bold text-yellow-900">{{ number_format($subscriptionMetrics['trial']) }}</div>
                </div>
                <div class="bg-red-50 p-4 rounded-lg">
                    <div class="text-sm text-red-600 font-medium">Cancelled</div>
                    <div class="text-2xl font-bold text-red-900">{{ number_format($subscriptionMetrics['cancelled']) }}</div>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <div class="text-sm text-gray-600 font-medium">Expired</div>
                    <div class="text-2xl font-bold text-gray-900">{{ number_format($subscriptionMetrics['expired']) }}</div>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @if(!empty($subscriptionMetrics['by_plan']))
                <div>
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Subscriptions by Plan</h3>
                    <div class="space-y-2">
                        @foreach($subscriptionMetrics['by_plan'] as $plan => $count)
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">{{ ucfirst(str_replace('plan_', '', $plan)) }}</span>
                            <span class="font-medium">{{ $count }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
                <div>
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Churn Rate (Last 30 Days)</h3>
                    <div class="text-3xl font-bold text-red-600">{{ $subscriptionMetrics['churn_rate'] }}%</div>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Trial to Paid Conversion</h3>
                    <div class="text-3xl font-bold text-green-600">{{ $subscriptionMetrics['conversion_rate'] }}%</div>
                </div>
            </div>
        </div>

        <!-- 4. Payment Collection Report -->
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-900">Payment Collection Report</h2>
                <div class="flex space-x-2">
                    <form method="POST" action="{{ route('admin.reports.export') }}" class="inline">
                        @csrf
                        <input type="hidden" name="report" value="payment">
                        <input type="hidden" name="date_from" value="{{ $dateFrom }}">
                        <input type="hidden" name="date_to" value="{{ $dateTo }}">
                        <button type="submit" name="type" value="csv" class="px-3 py-1 text-sm bg-gray-100 text-gray-700 rounded hover:bg-gray-200">CSV</button>
                        <button type="submit" name="type" value="excel" class="px-3 py-1 text-sm bg-gray-100 text-gray-700 rounded hover:bg-gray-200">Excel</button>
                        <button type="submit" name="type" value="pdf" class="px-3 py-1 text-sm bg-gray-100 text-gray-700 rounded hover:bg-gray-200">PDF</button>
                    </form>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-green-50 p-4 rounded-lg">
                    <div class="text-sm text-green-600 font-medium">Total Collected</div>
                    <div class="text-2xl font-bold text-green-900">KES {{ number_format($paymentCollection['total_collected'], 2) }}</div>
                    @if($comparisonData)
                    <div class="text-xs text-gray-500 mt-1">
                        Previous: KES {{ number_format($comparisonData['payments']['total_collected'] ?? 0, 2) }}
                    </div>
                    @endif
                </div>
                <div class="bg-red-50 p-4 rounded-lg">
                    <div class="text-sm text-red-600 font-medium">Outstanding</div>
                    <div class="text-2xl font-bold text-red-900">KES {{ number_format($paymentCollection['outstanding'], 2) }}</div>
                </div>
                <div class="bg-blue-50 p-4 rounded-lg">
                    <div class="text-sm text-blue-600 font-medium">Collection Rate</div>
                    <div class="text-2xl font-bold text-blue-900">{{ $paymentCollection['collection_rate'] }}%</div>
                </div>
                <div class="bg-indigo-50 p-4 rounded-lg">
                    <div class="text-sm text-indigo-600 font-medium">Period</div>
                    <div class="text-sm font-medium text-indigo-900">
                        {{ \Carbon\Carbon::parse($dateFrom)->format('M d') }} - {{ \Carbon\Carbon::parse($dateTo)->format('M d, Y') }}
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @if(!empty($paymentCollection['by_status']))
                <div>
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Payments by Status</h3>
                    <div class="space-y-2">
                        @foreach($paymentCollection['by_status'] as $status => $info)
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 capitalize">{{ str_replace('_', ' ', $status) }}</span>
                            <div class="text-right">
                                <div class="font-medium">{{ $info['count'] }} payments</div>
                                <div class="text-xs text-gray-500">KES {{ number_format($info['total'], 2) }}</div>
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
                        @foreach($paymentCollection['by_method'] as $method => $info)
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 capitalize">{{ str_replace('_', ' ', $method) }}</span>
                            <div class="text-right">
                                <div class="font-medium">{{ $info['count'] }} payments</div>
                                <div class="text-xs text-gray-500">KES {{ number_format($info['total'], 2) }}</div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
