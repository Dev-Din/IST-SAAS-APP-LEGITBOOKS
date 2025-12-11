@extends('layouts.tenant')

@section('title', 'Chart of Accounts')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="py-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Chart of Accounts</h1>
            @perm('manage_chart_of_accounts')
            <a href="{{ route('tenant.chart-of-accounts.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white" style="background-color: var(--brand-primary);">
                Add Account
            </a>
            @endperm
        </div>

        @anyperm(['manage_chart_of_accounts', 'view_chart_of_accounts'])
        <!-- Toolbar with Actions and Search -->
        <div class="bg-white shadow rounded-lg mb-4">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <!-- Left Side - Action Buttons -->
                    <div class="flex items-center space-x-3">
                        @perm('manage_chart_of_accounts')
                        <button type="button" id="delete-selected" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                            Delete
                        </button>
                        <button type="button" id="archive-selected" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                            Archive
                        </button>
                        <button type="button" id="change-tax-rate" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                            Change Tax Rate
                        </button>
                        <span id="selection-status" class="ml-4 text-sm text-gray-500">No accounts selected</span>
                        @endperm
                    </div>
                    
                    <!-- Right Side - Search -->
                    <div class="flex items-center space-x-2">
                        <form method="GET" action="{{ route('tenant.chart-of-accounts.index') }}" class="flex items-center space-x-2">
                            <input type="text" name="search" value="{{ $search }}" placeholder="Search accounts..." class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <input type="hidden" name="sort_by" value="{{ $sortBy }}">
                            <input type="hidden" name="sort_order" value="{{ $sortOrder }}">
                            <button type="submit" class="px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white" style="background-color: var(--brand-primary);">
                                Search
                            </button>
                            @if($search)
                            <a href="{{ route('tenant.chart-of-accounts.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                Clear
                            </a>
                            @endif
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Accounts Table -->
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left">
                                <input type="checkbox" id="select-all" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <label for="select-all" class="ml-2 text-xs font-medium text-gray-500 uppercase tracking-wider">Select all accounts</label>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <a href="{{ route('tenant.chart-of-accounts.index', ['sort_by' => 'code', 'sort_order' => $sortBy === 'code' && $sortOrder === 'asc' ? 'desc' : 'asc', 'search' => $search]) }}" class="flex items-center">
                                    Code
                                    @if($sortBy === 'code')
                                        <svg class="ml-1 h-4 w-4 {{ $sortOrder === 'asc' ? '' : 'transform rotate-180' }}" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                        </svg>
                                    @endif
                                </a>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <a href="{{ route('tenant.chart-of-accounts.index', ['sort_by' => 'name', 'sort_order' => $sortBy === 'name' && $sortOrder === 'asc' ? 'desc' : 'asc', 'search' => $search]) }}" class="flex items-center">
                                    Name
                                    @if($sortBy === 'name')
                                        <svg class="ml-1 h-4 w-4 {{ $sortOrder === 'asc' ? '' : 'transform rotate-180' }}" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                        </svg>
                                    @endif
                                </a>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <a href="{{ route('tenant.chart-of-accounts.index', ['sort_by' => 'type', 'sort_order' => $sortBy === 'type' && $sortOrder === 'asc' ? 'desc' : 'asc', 'search' => $search]) }}" class="flex items-center">
                                    Type
                                    @if($sortBy === 'type')
                                        <svg class="ml-1 h-4 w-4 {{ $sortOrder === 'asc' ? '' : 'transform rotate-180' }}" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                        </svg>
                                    @endif
                                </a>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tax Rate</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">YTD</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($chartOfAccounts as $account)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input type="checkbox" name="account_ids[]" value="{{ $account->id }}" class="account-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $account->code }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm">
                                    <a href="{{ route('tenant.chart-of-accounts.show', $account) }}" class="text-blue-600 hover:text-blue-900 font-medium">
                                        {{ $account->name }}
                                    </a>
                                    @if($account->category)
                                    <div class="text-xs text-gray-500 mt-1">
                                        {{ ucfirst(str_replace('_', ' ', $account->category)) }}
                                    </div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 capitalize">{{ ucfirst($account->type) }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    @if($account->type === 'revenue')
                                        Tax on Sales (0%)
                                    @elseif($account->type === 'expense')
                                        Tax on Purchases (0%)
                                    @else
                                        Tax Exempt (0%)
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="{{ route('tenant.chart-of-accounts.show', $account) }}" class="text-sm text-blue-600 hover:text-blue-900 font-medium">
                                    {{ number_format($account->ytd_balance ?? 0, 2) }}
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500">
                                No accounts found. <a href="{{ route('tenant.chart-of-accounts.create') }}" class="text-indigo-600 hover:text-indigo-900 font-medium">Create your first account</a> to get started.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endanyperm
    </div>
</div>

@perm('manage_chart_of_accounts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('select-all');
    const accountCheckboxes = document.querySelectorAll('.account-checkbox');
    const deleteButton = document.getElementById('delete-selected');
    const archiveButton = document.getElementById('archive-selected');
    const changeTaxRateButton = document.getElementById('change-tax-rate');
    const selectionStatus = document.getElementById('selection-status');
    
    // Select all functionality
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            accountCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateSelectionStatus();
        });
    }
    
    // Individual checkbox change
    accountCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateSelectAllState();
            updateSelectionStatus();
        });
    });
    
    function updateSelectAllState() {
        if (selectAllCheckbox) {
            const allChecked = Array.from(accountCheckboxes).every(cb => cb.checked);
            const someChecked = Array.from(accountCheckboxes).some(cb => cb.checked);
            selectAllCheckbox.checked = allChecked;
            selectAllCheckbox.indeterminate = someChecked && !allChecked;
        }
    }
    
    function updateSelectionStatus() {
        const selectedCount = Array.from(accountCheckboxes).filter(cb => cb.checked).length;
        const isDisabled = selectedCount === 0;
        
        if (deleteButton) deleteButton.disabled = isDisabled;
        if (archiveButton) archiveButton.disabled = isDisabled;
        if (changeTaxRateButton) changeTaxRateButton.disabled = isDisabled;
        
        if (selectionStatus) {
            if (selectedCount === 0) {
                selectionStatus.textContent = 'No accounts selected';
            } else {
                selectionStatus.textContent = `${selectedCount} account(s) selected`;
            }
        }
    }
    
    // Delete functionality
    if (deleteButton) {
        deleteButton.addEventListener('click', function() {
            const selectedIds = Array.from(accountCheckboxes)
                .filter(cb => cb.checked)
                .map(cb => cb.value);
            
            if (selectedIds.length === 0) return;
            
            if (confirm(`Are you sure you want to delete ${selectedIds.length} account(s)?`)) {
                // Implement delete functionality
                console.log('Delete accounts:', selectedIds);
            }
        });
    }
    
    // Archive functionality
    if (archiveButton) {
        archiveButton.addEventListener('click', function() {
            const selectedIds = Array.from(accountCheckboxes)
                .filter(cb => cb.checked)
                .map(cb => cb.value);
            
            if (selectedIds.length === 0) return;
            
            if (confirm(`Are you sure you want to archive ${selectedIds.length} account(s)?`)) {
                // Implement archive functionality
                console.log('Archive accounts:', selectedIds);
            }
        });
    }
    
    // Change tax rate functionality
    if (changeTaxRateButton) {
        changeTaxRateButton.addEventListener('click', function() {
            const selectedIds = Array.from(accountCheckboxes)
                .filter(cb => cb.checked)
                .map(cb => cb.value);
            
            if (selectedIds.length === 0) return;
            
            // Implement change tax rate functionality
            console.log('Change tax rate for accounts:', selectedIds);
        });
    }
});
</script>
@endperm
@endsection
