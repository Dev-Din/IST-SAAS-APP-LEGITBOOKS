@extends('layouts.marketing')

@section('title', 'Features - LegitBooks')

@section('content')
<!-- Hero -->
<section class="bg-white py-12 sm:py-16 w-full overflow-x-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
        <div class="text-center">
            <h1 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-3 sm:mb-4 px-2">Powerful features for your business</h1>
            <p class="text-lg sm:text-xl text-gray-600 max-w-3xl mx-auto px-2">Everything you need to manage your finances, all in one place.</p>
        </div>
    </div>
</section>

<!-- Invoicing & Payments -->
<section class="py-12 sm:py-16 bg-gray-50 w-full overflow-x-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 sm:gap-12 items-center">
            <div>
                <h2 class="text-3xl font-bold text-gray-900 mb-6">Invoicing & Payments</h2>
                <ul class="space-y-4">
                    <li class="flex items-start">
                        <svg class="h-6 w-6 text-green-500 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <div>
                            <strong class="text-gray-900">Professional Invoices</strong>
                            <p class="text-gray-600">Create and customize professional invoices with your branding. Generate PDFs instantly.</p>
                        </div>
                    </li>
                    <li class="flex items-start">
                        <svg class="h-6 w-6 text-green-500 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <div>
                            <strong class="text-gray-900">M-Pesa Integration</strong>
                            <p class="text-gray-600">Accept payments via M-Pesa and automatically reconcile transactions in real-time.</p>
                        </div>
                    </li>
                    <li class="flex items-start">
                        <svg class="h-6 w-6 text-green-500 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <div>
                            <strong class="text-gray-900">Payment Tracking</strong>
                            <p class="text-gray-600">Track invoice status, send payment reminders, and manage outstanding balances.</p>
                        </div>
                    </li>
                    <li class="flex items-start">
                        <svg class="h-6 w-6 text-green-500 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <div>
                            <strong class="text-gray-900">Recurring Invoices</strong>
                            <p class="text-gray-600">Set up recurring invoices for subscription-based services or regular clients.</p>
                        </div>
                    </li>
                </ul>
            </div>
            <div class="bg-white rounded-lg shadow-lg p-6 sm:p-8">
                <div class="text-center">
                    <svg class="h-32 w-32 mx-auto text-indigo-500 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Expenses & Bills -->
<section class="py-12 sm:py-16 bg-white w-full overflow-x-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 sm:gap-12 items-center">
            <div class="order-2 lg:order-1 bg-white rounded-lg shadow-lg p-8">
                <div class="text-center">
                    <svg class="h-32 w-32 mx-auto text-green-500 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
            </div>
            <div class="order-1 lg:order-2">
                <h2 class="text-3xl font-bold text-gray-900 mb-6">Expenses & Bills</h2>
                <ul class="space-y-4">
                    <li class="flex items-start">
                        <svg class="h-6 w-6 text-green-500 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <div>
                            <strong class="text-gray-900">Expense Tracking</strong>
                            <p class="text-gray-600">Record and categorize business expenses with receipt attachments.</p>
                        </div>
                    </li>
                    <li class="flex items-start">
                        <svg class="h-6 w-6 text-green-500 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <div>
                            <strong class="text-gray-900">Bill Management</strong>
                            <p class="text-gray-600">Track supplier bills, due dates, and payment schedules.</p>
                        </div>
                    </li>
                    <li class="flex items-start">
                        <svg class="h-6 w-6 text-green-500 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <div>
                            <strong class="text-gray-900">Account Management</strong>
                            <p class="text-gray-600">Manage multiple bank accounts, cash accounts, and M-Pesa balances.</p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- Reporting & Dashboards -->
<section class="py-12 sm:py-16 bg-gray-50 w-full overflow-x-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 sm:gap-12 items-center">
            <div>
                <h2 class="text-3xl font-bold text-gray-900 mb-6">Reporting & Dashboards</h2>
                <ul class="space-y-4">
                    <li class="flex items-start">
                        <svg class="h-6 w-6 text-green-500 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <div>
                            <strong class="text-gray-900">Financial Dashboards</strong>
                            <p class="text-gray-600">Real-time overview of your business finances with visual charts and graphs.</p>
                        </div>
                    </li>
                    <li class="flex items-start">
                        <svg class="h-6 w-6 text-green-500 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <div>
                            <strong class="text-gray-900">Profit & Loss Reports</strong>
                            <p class="text-gray-600">Generate comprehensive P&L statements for any date range.</p>
                        </div>
                    </li>
                    <li class="flex items-start">
                        <svg class="h-6 w-6 text-green-500 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <div>
                            <strong class="text-gray-900">Balance Sheets</strong>
                            <p class="text-gray-600">View your assets, liabilities, and equity at any point in time.</p>
                        </div>
                    </li>
                    <li class="flex items-start">
                        <svg class="h-6 w-6 text-green-500 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <div>
                            <strong class="text-gray-900">Aging Reports</strong>
                            <p class="text-gray-600">Track outstanding invoices and accounts receivable aging.</p>
                        </div>
                    </li>
                </ul>
            </div>
            <div class="bg-white rounded-lg shadow-lg p-6 sm:p-8">
                <div class="text-center">
                    <svg class="h-32 w-32 mx-auto text-blue-500 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Double-Entry & Audit Logs -->
<section class="py-12 sm:py-16 bg-white w-full overflow-x-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 sm:gap-12 items-center">
            <div class="order-2 lg:order-1 bg-white rounded-lg shadow-lg p-8">
                <div class="text-center">
                    <svg class="h-32 w-32 mx-auto text-purple-500 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
            </div>
            <div class="order-1 lg:order-2">
                <h2 class="text-3xl font-bold text-gray-900 mb-6">Double-Entry & Audit Logs</h2>
                <ul class="space-y-4">
                    <li class="flex items-start">
                        <svg class="h-6 w-6 text-green-500 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <div>
                            <strong class="text-gray-900">Automatic Journal Entries</strong>
                            <p class="text-gray-600">Every transaction creates balanced journal entries automatically.</p>
                        </div>
                    </li>
                    <li class="flex items-start">
                        <svg class="h-6 w-6 text-green-500 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <div>
                            <strong class="text-gray-900">Complete Audit Trail</strong>
                            <p class="text-gray-600">Track every change with detailed audit logs showing who did what and when.</p>
                        </div>
                    </li>
                    <li class="flex items-start">
                        <svg class="h-6 w-6 text-green-500 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <div>
                            <strong class="text-gray-900">Chart of Accounts</strong>
                            <p class="text-gray-600">Organize your accounts with a flexible chart of accounts structure.</p>
                        </div>
                    </li>
                    <li class="flex items-start">
                        <svg class="h-6 w-6 text-green-500 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <div>
                            <strong class="text-gray-900">Data Integrity</strong>
                            <p class="text-gray-600">Ensures your books always balance with automatic validation.</p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="py-12 sm:py-16 bg-gray-900 w-full overflow-x-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center w-full">
        <h2 class="text-2xl sm:text-3xl font-bold text-white mb-3 sm:mb-4 px-2">Ready to experience these features?</h2>
        <p class="text-lg sm:text-xl text-gray-300 mb-6 sm:mb-8 px-2">Start your free trial today.</p>
        <a href="{{ route('tenant.auth.register') }}" class="inline-flex items-center px-8 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700">
            Start free trial
        </a>
    </div>
</section>
@endsection

