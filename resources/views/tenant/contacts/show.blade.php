@extends('layouts.tenant')

@section('title', $contact->name)

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="py-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-900">{{ $contact->name }}</h1>
            @perm('manage_contacts')
            <a href="{{ route('tenant.contacts.edit', $contact) }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white" style="background-color: var(--brand-primary);">
                Edit
            </a>
            @endperm
        </div>

        @anyperm(['manage_contacts', 'view_contacts'])
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Contact Information</h3>
            </div>
            <div class="px-4 py-5 sm:p-6">
                <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Type</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                @if($contact->type === 'customer') bg-blue-100 text-blue-800
                                @else bg-green-100 text-green-800
                                @endif">
                                {{ ucfirst($contact->type) }}
                            </span>
                        </dd>
                    </div>
                    @if($contact->email)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Email</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $contact->email }}</dd>
                    </div>
                    @endif
                    @if($contact->phone)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Phone</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $contact->phone }}</dd>
                    </div>
                    @endif
                    @if($contact->address)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Address</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $contact->address }}</dd>
                    </div>
                    @endif
                    @if($contact->tax_id)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Tax ID</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $contact->tax_id }}</dd>
                    </div>
                    @endif
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Tax Rate</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ number_format($contact->tax_rate ?? 0, 2) }}%</dd>
                    </div>
                </dl>
            </div>
        </div>
        @else
        <div class="bg-white shadow overflow-hidden sm:rounded-lg p-8 text-center text-gray-500">
            You do not have permission to view this contact.
        </div>
        @endanyperm

        {{-- Invoices Section (for customers) --}}
        @if($contact->type === 'customer' && isset($invoices))
        <div class="mt-6 bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Invoices</h3>
            </div>
            <div class="divide-y divide-gray-200">
                @forelse($invoices as $invoice)
                <a href="{{ route('tenant.invoices.show', $invoice) }}" class="block hover:bg-gray-50">
                    <div class="px-4 py-4 sm:px-6">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <p class="text-sm font-medium text-indigo-600">
                                    {{ $invoice->invoice_number }}
                                </p>
                                @php
                                    $outstanding = $invoice->getOutstandingAmount();
                                    $isPaid = $invoice->status === 'paid' || $outstanding <= 0;
                                @endphp
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    @if($isPaid) bg-green-100 text-green-800
                                    @else bg-yellow-100 text-yellow-800
                                    @endif">
                                    @if($isPaid) Paid @else Pending @endif
                                </span>
                            </div>
                            <div class="flex items-center space-x-4">
                                <p class="text-sm font-medium text-gray-900">
                                    KES {{ number_format($invoice->total, 2) }}
                                </p>
                            </div>
                        </div>
                        <div class="mt-2 sm:flex sm:justify-between">
                            <div class="sm:flex">
                                <p class="text-sm text-gray-500">
                                    Date: {{ $invoice->invoice_date->format('d/m/Y') }}
                                    @if($invoice->due_date)
                                    | Due: {{ $invoice->due_date->format('d/m/Y') }}
                                    @endif
                                </p>
                            </div>
                            @if(!$isPaid && $outstanding > 0)
                            <div class="mt-2 sm:mt-0">
                                <p class="text-xs text-orange-600">
                                    Outstanding: KES {{ number_format($outstanding, 2) }}
                                </p>
                            </div>
                            @endif
                        </div>
                    </div>
                </a>
                @empty
                <div class="px-4 py-8 text-center text-gray-500">
                    No invoices found for this customer.
                </div>
                @endforelse
            </div>
        </div>
        @endif

        {{-- Bills Section (for suppliers) --}}
        @if($contact->type === 'supplier' && isset($bills))
        <div class="mt-6 bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Bills</h3>
            </div>
            <div class="divide-y divide-gray-200">
                @forelse($bills as $bill)
                <a href="{{ route('tenant.bills.show', $bill) }}" class="block hover:bg-gray-50">
                    <div class="px-4 py-4 sm:px-6">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <p class="text-sm font-medium text-indigo-600">
                                    {{ $bill->bill_number }}
                                </p>
                                @php
                                    $outstanding = $bill->getOutstandingAmount();
                                    $isPaid = $bill->status === 'paid' || $outstanding <= 0;
                                @endphp
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    @if($isPaid) bg-green-100 text-green-800
                                    @else bg-yellow-100 text-yellow-800
                                    @endif">
                                    @if($isPaid) Paid @else Pending @endif
                                </span>
                            </div>
                            <div class="flex items-center space-x-4">
                                <p class="text-sm font-medium text-gray-900">
                                    KES {{ number_format($bill->total, 2) }}
                                </p>
                            </div>
                        </div>
                        <div class="mt-2 sm:flex sm:justify-between">
                            <div class="sm:flex">
                                <p class="text-sm text-gray-500">
                                    Date: {{ $bill->bill_date->format('d/m/Y') }}
                                    @if($bill->due_date)
                                    | Due: {{ $bill->due_date->format('d/m/Y') }}
                                    @endif
                                </p>
                            </div>
                            @if(!$isPaid && $outstanding > 0)
                            <div class="mt-2 sm:mt-0">
                                <p class="text-xs text-orange-600">
                                    Outstanding: KES {{ number_format($outstanding, 2) }}
                                </p>
                            </div>
                            @endif
                        </div>
                    </div>
                </a>
                @empty
                <div class="px-4 py-8 text-center text-gray-500">
                    No bills found for this supplier.
                </div>
                @endforelse
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

