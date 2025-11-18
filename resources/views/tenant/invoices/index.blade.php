@extends('layouts.tenant')

@section('title', 'Invoices')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="py-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Invoices</h1>
            <a href="{{ route('tenant.invoices.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white" style="background-color: var(--brand-primary);">
                Create Invoice
            </a>
        </div>

        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <ul class="divide-y divide-gray-200">
                @forelse($invoices as $invoice)
                <li>
                    <a href="{{ route('tenant.invoices.show', $invoice) }}" class="block hover:bg-gray-50">
                        <div class="px-4 py-4 sm:px-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <p class="text-sm font-medium text-indigo-600 truncate">
                                        {{ $invoice->invoice_number }}
                                    </p>
                                    <p class="ml-2 flex-shrink-0 flex">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            @if($invoice->status === 'paid') bg-green-100 text-green-800
                                            @elseif($invoice->status === 'sent') bg-blue-100 text-blue-800
                                            @elseif($invoice->status === 'overdue') bg-red-100 text-red-800
                                            @else bg-gray-100 text-gray-800
                                            @endif">
                                            {{ ucfirst($invoice->status) }}
                                        </span>
                                    </p>
                                </div>
                                <div class="ml-2 flex-shrink-0 flex">
                                    <p class="text-sm text-gray-900">
                                        {{ number_format($invoice->total, 2) }}
                                    </p>
                                </div>
                            </div>
                            <div class="mt-2 sm:flex sm:justify-between">
                                <div class="sm:flex">
                                    <p class="flex items-center text-sm text-gray-500">
                                        {{ $invoice->contact->name }}
                                    </p>
                                </div>
                                <div class="mt-2 flex items-center text-sm text-gray-500 sm:mt-0">
                                    <p>
                                        Date: {{ $invoice->invoice_date->format('d/m/Y') }}
                                        @if($invoice->due_date)
                                        | Due: {{ $invoice->due_date->format('d/m/Y') }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    </a>
                </li>
                @empty
                <li class="px-4 py-8 text-center text-gray-500">
                    No invoices found. <a href="{{ route('tenant.invoices.create') }}" class="text-indigo-600 hover:text-indigo-900">Create your first invoice</a>
                </li>
                @endforelse
            </ul>
        </div>

        <div class="mt-4">
            {{ $invoices->links() }}
        </div>
    </div>
</div>
@endsection

