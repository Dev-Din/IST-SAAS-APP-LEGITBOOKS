@extends('layouts.tenant')

@section('title', 'Add Chart of Account')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="py-6">
        <h1 class="text-3xl font-bold text-gray-900 mb-6">Add Chart of Account</h1>

        <form method="POST" action="{{ route('tenant.chart-of-accounts.store') }}" class="bg-white shadow-sm rounded-lg p-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700">Code *</label>
                    <input type="text" name="code" id="code" value="{{ old('code') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('code')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Unique identifier for this account</p>
                </div>

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Name *</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700">Type *</label>
                    <select name="type" id="type" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Select type</option>
                        <option value="asset" {{ old('type') == 'asset' ? 'selected' : '' }}>Asset</option>
                        <option value="liability" {{ old('type') == 'liability' ? 'selected' : '' }}>Liability</option>
                        <option value="equity" {{ old('type') == 'equity' ? 'selected' : '' }}>Equity</option>
                        <option value="revenue" {{ old('type') == 'revenue' ? 'selected' : '' }}>Revenue</option>
                        <option value="expense" {{ old('type') == 'expense' ? 'selected' : '' }}>Expense</option>
                    </select>
                    @error('type')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700">Category</label>
                    <select name="category" id="category" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Select category (optional)</option>
                        <option value="current_asset" {{ old('category') == 'current_asset' ? 'selected' : '' }}>Current Asset</option>
                        <option value="fixed_asset" {{ old('category') == 'fixed_asset' ? 'selected' : '' }}>Fixed Asset</option>
                        <option value="current_liability" {{ old('category') == 'current_liability' ? 'selected' : '' }}>Current Liability</option>
                        <option value="long_term_liability" {{ old('category') == 'long_term_liability' ? 'selected' : '' }}>Long Term Liability</option>
                        <option value="equity" {{ old('category') == 'equity' ? 'selected' : '' }}>Equity</option>
                        <option value="revenue" {{ old('category') == 'revenue' ? 'selected' : '' }}>Revenue</option>
                        <option value="expense" {{ old('category') == 'expense' ? 'selected' : '' }}>Expense</option>
                        <option value="cost_of_sales" {{ old('category') == 'cost_of_sales' ? 'selected' : '' }}>Cost of Sales</option>
                    </select>
                    @error('category')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="parent_id" class="block text-sm font-medium text-gray-700">Parent Account</label>
                    <select name="parent_id" id="parent_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">None (Top-level account)</option>
                        @foreach($parentAccounts as $parent)
                        <option value="{{ $parent->id }}" {{ old('parent_id') == $parent->id ? 'selected' : '' }}>
                            {{ $parent->code }} - {{ $parent->name }}
                        </option>
                        @endforeach
                    </select>
                    @error('parent_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Optional: Select a parent account to create a sub-account</p>
                </div>

                <div>
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="ml-2 text-sm text-gray-700">Active</span>
                    </label>
                    @error('is_active')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex justify-end space-x-3">
                <a href="{{ route('tenant.chart-of-accounts.index') }}" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white" style="background-color: var(--brand-primary);">
                    Create Account
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

