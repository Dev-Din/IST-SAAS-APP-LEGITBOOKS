@extends('layouts.tenant')

@section('title', 'Contacts')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="py-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Contacts</h1>
            <a href="{{ route('tenant.contacts.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white" style="background-color: var(--brand-primary);">
                Add Contact
            </a>
        </div>

        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <ul class="divide-y divide-gray-200">
                @forelse($contacts as $contact)
                <li>
                    <a href="{{ route('tenant.contacts.show', $contact) }}" class="block hover:bg-gray-50">
                        <div class="px-4 py-4 sm:px-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <p class="text-sm font-medium text-indigo-600 truncate">
                                        {{ $contact->name }}
                                    </p>
                                    <p class="ml-2 flex-shrink-0 flex">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            @if($contact->type === 'customer') bg-blue-100 text-blue-800
                                            @else bg-green-100 text-green-800
                                            @endif">
                                            {{ ucfirst($contact->type) }}
                                        </span>
                                    </p>
                                </div>
                                <div class="ml-2 flex-shrink-0 flex">
                                    @if($contact->email)
                                    <p class="text-sm text-gray-500">{{ $contact->email }}</p>
                                    @endif
                                </div>
                            </div>
                            <div class="mt-2 sm:flex sm:justify-between">
                                <div class="sm:flex">
                                    @if($contact->phone)
                                    <p class="flex items-center text-sm text-gray-500">
                                        {{ $contact->phone }}
                                    </p>
                                    @endif
                                </div>
                                <div class="mt-2 flex items-center text-sm text-gray-500 sm:mt-0">
                                    @if($contact->address)
                                    <p>{{ $contact->address }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </a>
                </li>
                @empty
                <li class="px-4 py-8 text-center text-gray-500">
                    No contacts found. <a href="{{ route('tenant.contacts.create') }}" class="text-indigo-600 hover:text-indigo-900">Add your first contact</a>
                </li>
                @endforelse
            </ul>
        </div>

        <div class="mt-4">
            {{ $contacts->links() }}
        </div>
    </div>
</div>
@endsection

