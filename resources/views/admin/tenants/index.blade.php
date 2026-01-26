@extends('layouts.admin')

@section('title', 'Tenants')

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

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
                <li class="tenant-card" data-tenant-id="{{ $tenant->id }}">
                    <!-- Tenant Card Header -->
                    <div class="px-4 py-4 sm:px-6 hover:bg-gray-50 cursor-pointer tenant-card-header">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center flex-1">
                                <button type="button" class="mr-3 text-gray-400 hover:text-gray-600 expand-toggle" data-tenant-id="{{ $tenant->id }}">
                                    <svg class="w-5 h-5 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </button>
                                <div class="flex-shrink-0">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        {{ $tenant->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ ucfirst($tenant->status) }}
                                    </span>
                                </div>
                                <div class="ml-4 flex-1">
                                    <div class="text-sm font-medium text-gray-900">{{ $tenant->name }}</div>
                                    <div class="text-sm text-gray-500">{{ $tenant->email }}</div>
                                    <div class="text-xs text-gray-400 mt-1">Hash: {{ $tenant->tenant_hash }}</div>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <a href="{{ route('admin.tenants.show', $tenant) }}" class="text-blue-600 hover:text-blue-900 text-sm">View</a>
                                @if($tenant->status === 'active')
                                <form method="POST" action="{{ route('admin.tenants.suspend', $tenant) }}" class="inline" onclick="event.stopPropagation()">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="text-red-600 hover:text-red-900 text-sm">Suspend</button>
                                </form>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Expanded Content (Hidden by default) -->
                    <div class="tenant-details hidden border-t border-gray-200 bg-gray-50" data-tenant-id="{{ $tenant->id }}">
                        <div class="px-4 py-4">
                            <!-- Loading State -->
                            <div class="loading-state text-center py-8">
                                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900"></div>
                                <p class="mt-2 text-sm text-gray-600">Loading tenant details...</p>
                            </div>

                            <!-- Tabs -->
                            <div class="tabs-container hidden">
                                <div class="border-b border-gray-200 mb-4">
                                    <nav class="-mb-px flex space-x-8">
                                        <button class="tab-button active px-1 py-4 text-sm font-medium text-gray-900 border-b-2 border-indigo-500" data-tab="users">
                                            Users
                                        </button>
                                        <button class="tab-button px-1 py-4 text-sm font-medium text-gray-500 border-b-2 border-transparent hover:text-gray-700 hover:border-gray-300" data-tab="billing">
                                            Billing & Invoices
                                        </button>
                                    </nav>
                                </div>

                                <!-- Users Tab Content -->
                                <div id="tab-users-{{ $tenant->id }}" class="tab-content">
                                    <div class="mb-4 flex justify-between items-center">
                                        <div class="flex items-center space-x-3 flex-1">
                                            <input type="text" 
                                                   id="user-search-{{ $tenant->id }}" 
                                                   placeholder="Search users..." 
                                                   class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 flex-1 max-w-xs">
                                            <select id="user-role-filter-{{ $tenant->id }}" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                                <option value="">All Roles</option>
                                                <option value="owner">Owner</option>
                                                <option value="admin">Admin</option>
                                                <option value="user">User</option>
                                            </select>
                                        </div>
                                        <button type="button" 
                                                class="create-user-btn px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                                data-tenant-id="{{ $tenant->id }}">
                                            Create User
                                        </button>
                                    </div>

                                    <!-- Users List -->
                                    <div id="users-list-{{ $tenant->id }}" class="space-y-2">
                                        <!-- Users will be loaded here via AJAX -->
                                    </div>

                                    <!-- Users Pagination -->
                                    <div id="users-pagination-{{ $tenant->id }}" class="mt-4">
                                        <!-- Pagination will be loaded here -->
                                    </div>
                                </div>

                                <!-- Billing & Invoices Tab Content -->
                                <div id="tab-billing-{{ $tenant->id }}" class="tab-content hidden">
                                    <!-- Subscription Summary -->
                                    <div id="subscription-summary-{{ $tenant->id }}" class="mb-6 p-4 bg-white rounded-lg shadow-sm">
                                        <!-- Subscription info will be loaded here -->
                                    </div>

                                    <!-- Invoice Filters -->
                                    <div class="mb-4 p-4 bg-white rounded-lg shadow-sm">
                                        <div class="flex flex-wrap items-end gap-3 mb-4">
                                            <div class="flex space-x-2">
                                                <button type="button" class="invoice-filter-btn px-3 py-1 text-sm rounded-md border border-gray-300 hover:bg-gray-50 active" data-status="all">All</button>
                                                <button type="button" class="invoice-filter-btn px-3 py-1 text-sm rounded-md border border-gray-300 hover:bg-gray-50" data-status="paid">Paid</button>
                                                <button type="button" class="invoice-filter-btn px-3 py-1 text-sm rounded-md border border-gray-300 hover:bg-gray-50" data-status="due">Due</button>
                                                <button type="button" class="invoice-filter-btn px-3 py-1 text-sm rounded-md border border-gray-300 hover:bg-gray-50" data-status="overdue">Overdue</button>
                                            </div>
                                            <div class="flex items-center space-x-2">
                                                <input type="date" id="invoice-date-from-{{ $tenant->id }}" class="px-2 py-1 border border-gray-300 rounded text-sm">
                                                <span class="text-gray-500">to</span>
                                                <input type="date" id="invoice-date-to-{{ $tenant->id }}" class="px-2 py-1 border border-gray-300 rounded text-sm">
                                            </div>
                                            <select id="invoice-payment-method-{{ $tenant->id }}" class="px-2 py-1 border border-gray-300 rounded text-sm">
                                                <option value="">All Payment Methods</option>
                                                <option value="mpesa">M-Pesa</option>
                                                <option value="debit_card">Debit Card</option>
                                                <option value="credit_card">Credit Card</option>
                                                <option value="paypal">PayPal</option>
                                            </select>
                                            <button type="button" 
                                                    class="apply-invoice-filters-btn px-4 py-1 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700"
                                                    data-tenant-id="{{ $tenant->id }}">
                                                Filter
                                            </button>
                                            <button type="button" 
                                                    class="export-invoices-btn px-4 py-1 bg-gray-600 text-white text-sm rounded-md hover:bg-gray-700"
                                                    data-tenant-id="{{ $tenant->id }}">
                                                Export CSV
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Invoices Table -->
                                    <div id="invoices-list-{{ $tenant->id }}" class="bg-white rounded-lg shadow-sm overflow-hidden">
                                        <!-- Invoices will be loaded here via AJAX -->
                                    </div>

                                    <!-- Invoices Pagination -->
                                    <div id="invoices-pagination-{{ $tenant->id }}" class="mt-4">
                                        <!-- Pagination will be loaded here -->
                                    </div>
                                </div>
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

<!-- Create User Modal -->
<div id="create-user-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Create User</h3>
                <button type="button" class="close-modal text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <form id="create-user-form">
                @csrf
                <input type="hidden" id="modal-tenant-id" name="tenant_id">
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
                            <input type="text" name="first_name" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Last Name *</label>
                            <input type="text" name="last_name" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                        <input type="email" name="email" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Role *</label>
                        <select name="role_name" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Select Role</option>
                            <option value="admin">Admin</option>
                            <option value="user">User</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Permissions</label>
                        <div class="mt-2 space-y-2 max-h-48 overflow-y-auto border border-gray-200 rounded-md p-3">
                            @foreach(config('tenant_permissions.permissions', []) as $key => $permission)
                            <label class="flex items-center">
                                <input type="checkbox" name="permissions[]" value="{{ $key }}" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700">{{ $permission['label'] ?? $key }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    <div id="create-user-errors" class="text-red-600 text-sm hidden"></div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" class="close-modal px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Create & Send Invite</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Expand/collapse tenant cards
    document.querySelectorAll('.tenant-card-header').forEach(header => {
        header.addEventListener('click', function(e) {
            if (e.target.closest('a, button[type="submit"]')) {
                return; // Don't expand if clicking links/buttons
            }
            const tenantId = this.closest('.tenant-card').dataset.tenantId;
            toggleTenantCard(tenantId);
        });
    });

    // Tab switching
    document.querySelectorAll('.tab-button').forEach(button => {
        button.addEventListener('click', function() {
            const tenantId = this.closest('.tenant-card').dataset.tenantId;
            const tab = this.dataset.tab;
            switchTab(tenantId, tab);
        });
    });

    // User search and filter
    document.querySelectorAll('[id^="user-search-"]').forEach(input => {
        let timeout;
        input.addEventListener('input', function() {
            clearTimeout(timeout);
            const tenantId = this.id.replace('user-search-', '');
            timeout = setTimeout(() => loadUsers(tenantId), 500);
        });
    });

    document.querySelectorAll('[id^="user-role-filter-"]').forEach(select => {
        select.addEventListener('change', function() {
            const tenantId = this.id.replace('user-role-filter-', '');
            loadUsers(tenantId);
        });
    });

    // Create user modal
    document.querySelectorAll('.create-user-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const tenantId = this.dataset.tenantId;
            document.getElementById('modal-tenant-id').value = tenantId;
            document.getElementById('create-user-modal').classList.remove('hidden');
        });
    });

    document.querySelectorAll('.close-modal').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('create-user-modal').classList.add('hidden');
            document.getElementById('create-user-form').reset();
            document.getElementById('create-user-errors').classList.add('hidden');
        });
    });

    document.getElementById('create-user-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const tenantId = document.getElementById('modal-tenant-id').value;
        createUser(tenantId);
    });

    // Invoice filters
    document.querySelectorAll('.invoice-filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const tenantId = this.closest('.tenant-card').dataset.tenantId;
            document.querySelectorAll(`[data-tenant-id="${tenantId}"] .invoice-filter-btn`).forEach(b => b.classList.remove('active', 'bg-indigo-100'));
            this.classList.add('active', 'bg-indigo-100');
            loadInvoices(tenantId);
        });
    });

    document.querySelectorAll('.apply-invoice-filters-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const tenantId = this.dataset.tenantId;
            loadInvoices(tenantId);
        });
    });

    document.querySelectorAll('.export-invoices-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const tenantId = this.dataset.tenantId;
            exportInvoices(tenantId);
        });
    });
});

function toggleTenantCard(tenantId) {
    const card = document.querySelector(`.tenant-card[data-tenant-id="${tenantId}"]`);
    const details = card.querySelector('.tenant-details');
    const toggle = card.querySelector('.expand-toggle svg');
    
    if (details.classList.contains('hidden')) {
        details.classList.remove('hidden');
        toggle.classList.add('rotate-90');
        loadTenantDetails(tenantId);
    } else {
        details.classList.add('hidden');
        toggle.classList.remove('rotate-90');
    }
}

function switchTab(tenantId, tab) {
    // Update tab buttons
    document.querySelectorAll(`[data-tenant-id="${tenantId}"] .tab-button`).forEach(btn => {
        btn.classList.remove('active', 'text-gray-900', 'border-indigo-500');
        btn.classList.add('text-gray-500', 'border-transparent');
    });
    const activeBtn = document.querySelector(`[data-tenant-id="${tenantId}"] .tab-button[data-tab="${tab}"]`);
    activeBtn.classList.add('active', 'text-gray-900', 'border-indigo-500');
    activeBtn.classList.remove('text-gray-500', 'border-transparent');

    // Update tab content
    document.querySelectorAll(`[data-tenant-id="${tenantId}"] .tab-content`).forEach(content => {
        content.classList.add('hidden');
    });
    document.getElementById(`tab-${tab}-${tenantId}`).classList.remove('hidden');

    // Load data if needed
    if (tab === 'users') {
        loadUsers(tenantId);
    } else if (tab === 'billing') {
        loadInvoices(tenantId);
    }
}

function loadTenantDetails(tenantId) {
    const detailsDiv = document.querySelector(`.tenant-details[data-tenant-id="${tenantId}"]`);
    const loadingState = detailsDiv.querySelector('.loading-state');
    const tabsContainer = detailsDiv.querySelector('.tabs-container');

    loadingState.classList.remove('hidden');
    tabsContainer.classList.add('hidden');

    fetch(`/admin/tenants/${tenantId}/details`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        },
        credentials: 'same-origin',
    })
    .then(response => response.json())
    .then(data => {
        loadingState.classList.add('hidden');
        tabsContainer.classList.remove('hidden');

        // Update subscription summary
        updateSubscriptionSummary(tenantId, data.subscription);

        // Load initial tab data
        loadUsers(tenantId);
    })
    .catch(error => {
        console.error('Error loading tenant details:', error);
        loadingState.innerHTML = '<p class="text-red-600">Error loading tenant details. Please try again.</p>';
    });
}

function updateSubscriptionSummary(tenantId, subscription) {
    const summaryDiv = document.getElementById(`subscription-summary-${tenantId}`);
    if (!subscription) {
        summaryDiv.innerHTML = '<p class="text-gray-600">No subscription found.</p>';
        return;
    }

    summaryDiv.innerHTML = `
        <h4 class="text-lg font-semibold text-gray-900 mb-3">Subscription Summary</h4>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div>
                <div class="text-xs text-gray-500">Plan</div>
                <div class="text-sm font-medium">${subscription.plan_name || subscription.plan}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500">Status</div>
                <div class="text-sm font-medium capitalize">${subscription.status}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500">Started</div>
                <div class="text-sm font-medium">${subscription.started_at || 'N/A'}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500">Next Billing</div>
                <div class="text-sm font-medium">${subscription.next_billing_at || 'N/A'}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500">Payment Method</div>
                <div class="text-sm font-medium">${subscription.payment_method || 'N/A'}</div>
            </div>
        </div>
    `;
}

function loadUsers(tenantId, page = 1) {
    const search = document.getElementById(`user-search-${tenantId}`)?.value || '';
    const role = document.getElementById(`user-role-filter-${tenantId}`)?.value || '';
    const listDiv = document.getElementById(`users-list-${tenantId}`);
    const paginationDiv = document.getElementById(`users-pagination-${tenantId}`);

    listDiv.innerHTML = '<div class="text-center py-4"><div class="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-gray-900"></div></div>';

    const params = new URLSearchParams({ page, q: search, role });
    fetch(`/admin/tenants/${tenantId}/users?${params}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        },
        credentials: 'same-origin',
    })
    .then(response => response.json())
    .then(data => {
        if (data.users.length === 0 && data.invitations.length === 0) {
            listDiv.innerHTML = `
                <div class="text-center py-8 text-gray-500">
                    <p class="mb-2">No users found.</p>
                    <button type="button" class="create-user-btn text-indigo-600 hover:text-indigo-900" data-tenant-id="${tenantId}">Create your first user</button>
                </div>
            `;
            return;
        }

        let html = '';

        // Active users
        data.users.forEach(user => {
            const statusBadge = user.is_active 
                ? '<span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Active</span>'
                : '<span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Inactive</span>';
            
            const roleBadge = user.is_owner
                ? '<span class="px-2 py-1 text-xs rounded-full bg-purple-100 text-purple-800">Owner</span>'
                : `<span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">${user.role_name || 'User'}</span>`;

            html += `
                <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-gray-200 hover:bg-gray-50">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-800 font-semibold text-sm">
                            ${user.avatar}
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-900">${user.first_name || ''} ${user.last_name || ''} ${user.name || ''}</div>
                            <div class="text-xs text-gray-500">${user.email}</div>
                            <div class="flex items-center space-x-2 mt-1">
                                ${roleBadge}
                                ${statusBadge}
                            </div>
                        </div>
                    </div>
                    <div class="relative">
                        <button type="button" class="user-actions-btn text-gray-400 hover:text-gray-600" data-user-id="${user.id}" data-tenant-id="${tenantId}">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            `;
        });

        // Pending invitations
        data.invitations.forEach(invitation => {
            html += `
                <div class="flex items-center justify-between p-3 bg-yellow-50 rounded-lg border border-yellow-200">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-full bg-yellow-100 flex items-center justify-center text-yellow-800 font-semibold text-sm">
                            ${(invitation.first_name?.[0] || '') + (invitation.last_name?.[0] || '')}
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-900">${invitation.first_name} ${invitation.last_name}</div>
                            <div class="text-xs text-gray-500">${invitation.email} <span class="text-yellow-600">(Pending Invitation)</span></div>
                            <div class="text-xs text-gray-400 mt-1">Expires: ${invitation.expires_at}</div>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button type="button" class="resend-invite-btn px-3 py-1 text-xs bg-yellow-600 text-white rounded hover:bg-yellow-700" 
                                data-invitation-id="${invitation.id}" data-tenant-id="${tenantId}">
                            Resend
                        </button>
                        <button type="button" class="cancel-invite-btn px-3 py-1 text-xs bg-orange-600 text-white rounded hover:bg-orange-700" 
                                data-invitation-id="${invitation.id}" data-tenant-id="${tenantId}">
                            Cancel
                        </button>
                        <button type="button" class="delete-invite-btn px-3 py-1 text-xs bg-red-600 text-white rounded hover:bg-red-700" 
                                data-invitation-id="${invitation.id}" data-tenant-id="${tenantId}">
                            Delete
                        </button>
                    </div>
                </div>
            `;
        });

        listDiv.innerHTML = html;

        // Pagination
        if (data.pagination.last_page > 1) {
            let paginationHtml = '<div class="flex items-center justify-between">';
            if (data.pagination.current_page > 1) {
                paginationHtml += `<button onclick="loadUsers(${tenantId}, ${data.pagination.current_page - 1})" class="px-3 py-1 text-sm border rounded hover:bg-gray-50">Previous</button>`;
            } else {
                paginationHtml += '<span></span>';
            }
            paginationHtml += `<span class="text-sm text-gray-600">Page ${data.pagination.current_page} of ${data.pagination.last_page}</span>`;
            if (data.pagination.current_page < data.pagination.last_page) {
                paginationHtml += `<button onclick="loadUsers(${tenantId}, ${data.pagination.current_page + 1})" class="px-3 py-1 text-sm border rounded hover:bg-gray-50">Next</button>`;
            } else {
                paginationHtml += '<span></span>';
            }
            paginationHtml += '</div>';
            paginationDiv.innerHTML = paginationHtml;
        } else {
            paginationDiv.innerHTML = '';
        }

        // Attach event listeners
        attachUserActionListeners(tenantId);
    })
    .catch(error => {
        console.error('Error loading users:', error);
        listDiv.innerHTML = '<p class="text-red-600 text-center py-4">Error loading users. Please try again.</p>';
    });
}

function attachUserActionListeners(tenantId) {
    // User actions menu
    document.querySelectorAll(`[data-tenant-id="${tenantId}"] .user-actions-btn`).forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const userId = this.dataset.userId;
            showUserActionsMenu(userId, tenantId, this);
        });
    });

    // Resend invite
    document.querySelectorAll(`[data-tenant-id="${tenantId}"] .resend-invite-btn`).forEach(btn => {
        btn.addEventListener('click', function() {
            const invitationId = this.dataset.invitationId;
            resendInvitation(tenantId, invitationId);
        });
    });

    // Cancel invite
    document.querySelectorAll(`[data-tenant-id="${tenantId}"] .cancel-invite-btn`).forEach(btn => {
        btn.addEventListener('click', function() {
            const invitationId = this.dataset.invitationId;
            cancelInvitation(tenantId, invitationId);
        });
    });

    // Delete invite
    document.querySelectorAll(`[data-tenant-id="${tenantId}"] .delete-invite-btn`).forEach(btn => {
        btn.addEventListener('click', function() {
            const invitationId = this.dataset.invitationId;
            deleteInvitation(tenantId, invitationId);
        });
    });
}

function showUserActionsMenu(userId, tenantId, button) {
    // Simple implementation - could be enhanced with dropdown menu
    if (confirm('What would you like to do with this user?')) {
        // For now, just show alert - can be enhanced with proper dropdown
    }
}

function createUser(tenantId) {
    const form = document.getElementById('create-user-form');
    const formData = new FormData(form);
    const errorsDiv = document.getElementById('create-user-errors');

    errorsDiv.classList.add('hidden');
    errorsDiv.innerHTML = '';

    fetch(`/admin/tenants/${tenantId}/users`, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || formData.get('_token'),
        },
        body: formData,
        credentials: 'same-origin',
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('create-user-modal').classList.add('hidden');
            form.reset();
            loadUsers(tenantId);
            alert('User invitation sent successfully!');
        } else {
            errorsDiv.textContent = data.error || 'An error occurred';
            errorsDiv.classList.remove('hidden');
        }
    })
    .catch(error => {
        console.error('Error creating user:', error);
        errorsDiv.textContent = 'An error occurred. Please try again.';
        errorsDiv.classList.remove('hidden');
    });
}

function resendInvitation(tenantId, invitationId) {
    fetch(`/admin/tenants/${tenantId}/users/invitations/${invitationId}/resend`, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
        },
        credentials: 'same-origin',
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadUsers(tenantId);
            alert(data.message || 'Invitation resent successfully!');
        } else {
            alert(data.error || 'Failed to resend invitation.');
        }
    })
    .catch(error => {
        console.error('Error resending invitation:', error);
        alert('An error occurred. Please try again.');
    });
}

function cancelInvitation(tenantId, invitationId) {
    if (!confirm('Are you sure you want to cancel this invitation?')) {
        return;
    }

    fetch(`/admin/tenants/${tenantId}/users/invitations/${invitationId}/cancel`, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
        },
        credentials: 'same-origin',
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadUsers(tenantId);
            alert(data.message || 'Invitation cancelled successfully!');
        } else {
            alert(data.error || 'Failed to cancel invitation.');
        }
    })
    .catch(error => {
        console.error('Error cancelling invitation:', error);
        alert('An error occurred. Please try again.');
    });
}

function deleteInvitation(tenantId, invitationId) {
    if (!confirm('Are you sure you want to delete this invitation? This action cannot be undone.')) {
        return;
    }

    fetch(`/admin/tenants/${tenantId}/users/invitations/${invitationId}`, {
        method: 'DELETE',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
        },
        credentials: 'same-origin',
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadUsers(tenantId);
            alert(data.message || 'Invitation deleted successfully!');
        } else {
            alert(data.error || 'Failed to delete invitation.');
        }
    })
    .catch(error => {
        console.error('Error deleting invitation:', error);
        alert('An error occurred. Please try again.');
    });
}


function loadInvoices(tenantId, page = 1) {
    const status = document.querySelector(`[data-tenant-id="${tenantId}"] .invoice-filter-btn.active`)?.dataset.status || 'all';
    const dateFrom = document.getElementById(`invoice-date-from-${tenantId}`)?.value || '';
    const dateTo = document.getElementById(`invoice-date-to-${tenantId}`)?.value || '';
    const paymentMethod = document.getElementById(`invoice-payment-method-${tenantId}`)?.value || '';
    const listDiv = document.getElementById(`invoices-list-${tenantId}`);
    const paginationDiv = document.getElementById(`invoices-pagination-${tenantId}`);

    listDiv.innerHTML = '<div class="text-center py-4"><div class="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-gray-900"></div></div>';

    const params = new URLSearchParams({ page, status, date_from: dateFrom, date_to: dateTo, payment_method: paymentMethod });
    fetch(`/admin/tenants/${tenantId}/invoices?${params}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        },
        credentials: 'same-origin',
    })
    .then(response => response.json())
    .then(data => {
        if (data.invoices.length === 0) {
            listDiv.innerHTML = '<div class="text-center py-8 text-gray-500">No invoices found.</div>';
            return;
        }

        let html = '<div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200"><thead class="bg-gray-50"><tr>';
        html += '<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Invoice #</th>';
        html += '<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>';
        html += '<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>';
        html += '<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Due Date</th>';
        html += '<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>';
        html += '<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>';
        html += '<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>';
        html += '</tr></thead><tbody class="bg-white divide-y divide-gray-200">';

        data.invoices.forEach(invoice => {
            const statusColor = invoice.status === 'paid' ? 'bg-green-100 text-green-800' 
                : invoice.is_overdue ? 'bg-red-100 text-red-800' 
                : 'bg-yellow-100 text-yellow-800';
            
            html += `
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-sm font-medium text-gray-900">${invoice.invoice_number}</td>
                    <td class="px-4 py-3 text-sm text-gray-700">${invoice.client_name}</td>
                    <td class="px-4 py-3 text-sm text-gray-700">${invoice.invoice_date}</td>
                    <td class="px-4 py-3 text-sm text-gray-700">${invoice.due_date || 'N/A'}</td>
                    <td class="px-4 py-3 text-sm font-medium text-gray-900">KES ${invoice.total}</td>
                    <td class="px-4 py-3 text-sm"><span class="px-2 py-1 text-xs rounded-full ${statusColor}">${invoice.status}</span></td>
                    <td class="px-4 py-3 text-sm">
                        ${invoice.status === 'paid' && invoice.payment_narration.payment_amount ? 
                            `<button type="button" class="view-narration-btn text-indigo-600 hover:text-indigo-900" data-invoice-id="${invoice.id}" data-tenant-id="${tenantId}">View Details</button>` 
                            : '-'}
                    </td>
                </tr>
            `;
        });

        html += '</tbody></table></div>';
        listDiv.innerHTML = html;

        // Pagination
        if (data.pagination.last_page > 1) {
            let paginationHtml = '<div class="flex items-center justify-between">';
            if (data.pagination.current_page > 1) {
                paginationHtml += `<button onclick="loadInvoices(${tenantId}, ${data.pagination.current_page - 1})" class="px-3 py-1 text-sm border rounded hover:bg-gray-50">Previous</button>`;
            } else {
                paginationHtml += '<span></span>';
            }
            paginationHtml += `<span class="text-sm text-gray-600">Page ${data.pagination.current_page} of ${data.pagination.last_page}</span>`;
            if (data.pagination.current_page < data.pagination.last_page) {
                paginationHtml += `<button onclick="loadInvoices(${tenantId}, ${data.pagination.current_page + 1})" class="px-3 py-1 text-sm border rounded hover:bg-gray-50">Next</button>`;
            } else {
                paginationHtml += '<span></span>';
            }
            paginationHtml += '</div>';
            paginationDiv.innerHTML = paginationHtml;
        } else {
            paginationDiv.innerHTML = '';
        }

        // Attach narration view listeners
        document.querySelectorAll('.view-narration-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const invoiceId = this.dataset.invoiceId;
                viewInvoiceNarration(tenantId, invoiceId);
            });
        });
    })
    .catch(error => {
        console.error('Error loading invoices:', error);
        listDiv.innerHTML = '<p class="text-red-600 text-center py-4">Error loading invoices. Please try again.</p>';
    });
}

function viewInvoiceNarration(tenantId, invoiceId) {
    fetch(`/admin/tenants/${tenantId}/invoices/${invoiceId}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        },
        credentials: 'same-origin',
    })
    .then(response => response.json())
    .then(data => {
        const narration = data.invoice.payment_narration;
        if (!narration || !narration.narration) {
            alert('No payment narration available for this invoice.');
            return;
        }

        let message = 'Payment Narration:\n\n';
        message += narration.narration;
        
        alert(message);
    })
    .catch(error => {
        console.error('Error loading invoice narration:', error);
        alert('Error loading payment narration.');
    });
}

function exportInvoices(tenantId) {
    const status = document.querySelector(`[data-tenant-id="${tenantId}"] .invoice-filter-btn.active`)?.dataset.status || 'all';
    const dateFrom = document.getElementById(`invoice-date-from-${tenantId}`)?.value || '';
    const dateTo = document.getElementById(`invoice-date-to-${tenantId}`)?.value || '';
    const paymentMethod = document.getElementById(`invoice-payment-method-${tenantId}`)?.value || '';

    const params = new URLSearchParams({ status, date_from: dateFrom, date_to: dateTo, payment_method: paymentMethod });
    window.location.href = `/admin/tenants/${tenantId}/invoices/export?${params}`;
}
</script>
@endsection
