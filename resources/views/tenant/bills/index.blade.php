@extends('layouts.tenant')

@section('title', 'Bills')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="py-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Bills</h1>
            @perm('manage_bills')
            <a href="{{ route('tenant.bills.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white" style="background-color: var(--brand-primary);">
                Create Bill
            </a>
            @endperm
        </div>

        @anyperm(['manage_bills', 'view_bills'])
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <ul class="divide-y divide-gray-200">
                @forelse($bills as $bill)
                <li>
                    <a href="{{ route('tenant.bills.show', $bill) }}" class="block hover:bg-gray-50">
                        <div class="px-4 py-4 sm:px-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <p class="text-sm font-medium text-indigo-600 truncate">
                                        {{ $bill->bill_number }}
                                    </p>
                                    <p class="ml-2 flex-shrink-0 flex">
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
                                <div class="ml-2 flex-shrink-0 flex">
                                    <p class="text-sm font-medium text-gray-900">
                                        KES {{ number_format($bill->total, 2) }}
                                    </p>
                                </div>
                            </div>
                            <div class="mt-2 sm:flex sm:justify-between">
                                <div class="sm:flex">
                                    <p class="flex items-center text-sm text-gray-500">
                                        {{ $bill->contact->name }}
                                    </p>
                                </div>
                                <div class="mt-2 flex items-center text-sm text-gray-500 sm:mt-0">
                                    <p>
                                        Date: {{ $bill->bill_date->format('d/m/Y') }}
                                        @if($bill->due_date)
                                        | Due: {{ $bill->due_date->format('d/m/Y') }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                            @if($bill->getOutstandingAmount() > 0)
                            <div class="mt-2">
                                <p class="text-xs text-orange-600">
                                    Outstanding: KES {{ number_format($bill->getOutstandingAmount(), 2) }}
                                </p>
                            </div>
                            @endif
                        </div>
                    </a>
                </li>
                @empty
                <li class="px-4 py-8 text-center text-gray-500">
                    No bills found.
                    @perm('manage_bills')
                    <a href="{{ route('tenant.bills.create') }}" class="text-indigo-600 hover:text-indigo-900">Create your first bill</a>
                    @endperm
                </li>
                @endforelse
            </ul>
        </div>

        <div class="mt-4">
            {{ $bills->links() }}
        </div>
        @else
        <div class="bg-white shadow overflow-hidden sm:rounded-md p-8 text-center text-gray-500">
            You do not have permission to view bills.
        </div>
        @endanyperm
    </div>
</div>
@endsection

