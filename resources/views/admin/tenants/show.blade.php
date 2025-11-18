@extends('layouts.admin')

@section('title', 'Tenant Details')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="py-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-900">{{ $tenant->name }}</h1>
            <a href="{{ route('admin.tenants.index') }}" class="text-gray-600 hover:text-gray-900">← Back to Tenants</a>
        </div>

        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">Tenant Information</h2>
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Name</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $tenant->name }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Email</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $tenant->email }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Status</dt>
                    <dd class="mt-1">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            {{ $tenant->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ ucfirst($tenant->status) }}
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Tenant Hash</dt>
                    <dd class="mt-1 text-sm text-gray-900 font-mono">{{ $tenant->tenant_hash }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Access URL</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        <a href="/app/{{ $tenant->tenant_hash }}/dashboard" target="_blank" class="text-blue-600 hover:underline">
                            /app/{{ $tenant->tenant_hash }}/dashboard
                        </a>
                    </dd>
                </div>
            </dl>
        </div>

        <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-xl font-semibold mb-4">Branding Settings</h2>
            <form method="POST" action="{{ route('admin.tenants.branding', $tenant) }}">
                @csrf
                @method('PATCH')
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="branding_override">
                        Branding Override
                    </label>
                    <select name="branding_override" id="branding_override" class="shadow border rounded w-full py-2 px-3 text-gray-700">
                        <option value="">Use System Default ({{ env('BRANDING_MODE', 'A') }})</option>
                        <option value="A" {{ ($tenant->settings['branding_override'] ?? '') === 'A' ? 'selected' : '' }}>Mode A - Tenant Name Only</option>
                        <option value="B" {{ ($tenant->settings['branding_override'] ?? '') === 'B' ? 'selected' : '' }}>Mode B - LegitBooks Branding</option>
                        <option value="C" {{ ($tenant->settings['branding_override'] ?? '') === 'C' ? 'selected' : '' }}>Mode C - White Labeled</option>
                    </select>
                </div>
                <button type="submit" class="bg-slate-900 hover:bg-slate-800 text-white font-bold py-2 px-4 rounded">
                    Update Branding
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

