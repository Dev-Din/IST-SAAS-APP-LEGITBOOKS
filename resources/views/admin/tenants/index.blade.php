@extends('layouts.admin')

@section('title', 'Tenants')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="py-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Tenants</h1>
            <a href="{{ route('admin.tenants.create') }}" class="bg-slate-900 hover:bg-slate-800 text-white font-bold py-2 px-4 rounded">
                Create New Tenant
            </a>
        </div>

        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <ul class="divide-y divide-gray-200">
                @forelse($tenants as $tenant)
                <li>
                    <div class="px-4 py-4 sm:px-6 hover:bg-gray-50">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        {{ $tenant->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ ucfirst($tenant->status) }}
                                    </span>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $tenant->name }}</div>
                                    <div class="text-sm text-gray-500">{{ $tenant->email }}</div>
                                    <div class="text-xs text-gray-400 mt-1">Hash: {{ $tenant->tenant_hash }}</div>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <a href="{{ route('admin.tenants.show', $tenant) }}" class="text-blue-600 hover:text-blue-900 text-sm">View</a>
                                @if($tenant->status === 'active')
                                <form method="POST" action="{{ route('admin.tenants.suspend', $tenant) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="text-red-600 hover:text-red-900 text-sm">Suspend</button>
                                </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </li>
                @empty
                <li class="px-4 py-8 text-center text-gray-500">
                    No tenants found. <a href="{{ route('admin.tenants.create') }}" class="text-blue-600 hover:underline">Create one</a>
                </li>
                @endforelse
            </ul>
        </div>

        <div class="mt-4">
            {{ $tenants->links() }}
        </div>
    </div>
</div>
@endsection

