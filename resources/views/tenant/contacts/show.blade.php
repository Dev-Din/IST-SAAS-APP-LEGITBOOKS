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
                </dl>
            </div>
        </div>
        @else
        <div class="bg-white shadow overflow-hidden sm:rounded-lg p-8 text-center text-gray-500">
            You do not have permission to view this contact.
        </div>
        @endanyperm
    </div>
</div>
@endsection

