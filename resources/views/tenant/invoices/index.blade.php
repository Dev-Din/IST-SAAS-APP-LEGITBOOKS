@extends('layouts.tenant')

@section('title', 'Invoices')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="py-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Invoices</h1>
            <div class="flex space-x-3">
                @anyperm(['manage_invoices', 'view_invoices'])
                <div class="relative inline-block text-left">
                    <button type="button" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" id="download-menu-button" aria-expanded="false" aria-haspopup="true" onclick="toggleDownloadMenu()">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Download
                        <svg class="ml-2 -mr-1 h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                    <div class="hidden origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-50" id="download-menu" role="menu" aria-orientation="vertical">
                        <div class="py-1" role="none">
                            <a href="{{ route('tenant.invoices.export', ['format' => 'csv']) }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
                                <svg class="w-4 h-4 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Download as CSV
                            </a>
                            <a href="{{ route('tenant.invoices.export', ['format' => 'pdf']) }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
                                <svg class="w-4 h-4 mr-2 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                                Download as PDF
                            </a>
                        </div>
                    </div>
                </div>
                @endanyperm
                @perm('manage_invoices')
                <a href="{{ route('tenant.invoices.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white" style="background-color: var(--brand-primary);">
                    Create Invoice
                </a>
                @endperm
            </div>
        </div>

        @anyperm(['manage_invoices', 'view_invoices'])
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
                    No invoices found.
                    @perm('manage_invoices')
                    <a href="{{ route('tenant.invoices.create') }}" class="text-indigo-600 hover:text-indigo-900">Create your first invoice</a>
                    @endperm
                </li>
                @endforelse
            </ul>
        </div>

        <div class="mt-4">
            {{ $invoices->links() }}
        </div>
        @else
        <div class="bg-white shadow overflow-hidden sm:rounded-md p-8 text-center text-gray-500">
            You do not have permission to view invoices.
        </div>
        @endanyperm
    </div>
</div>

<script>
function toggleDownloadMenu() {
    const menu = document.getElementById('download-menu');
    menu.classList.toggle('hidden');
}

// Close menu when clicking outside
document.addEventListener('click', function(event) {
    const button = document.getElementById('download-menu-button');
    const menu = document.getElementById('download-menu');
    if (!button.contains(event.target) && !menu.contains(event.target)) {
        menu.classList.add('hidden');
    }
});
</script>
@endsection

