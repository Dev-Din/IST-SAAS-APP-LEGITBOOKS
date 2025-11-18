@extends('layouts.marketing')

@section('title', 'LegitBooks - Simple, Accurate Cloud Accounting')

@section('content')
<!-- Hero Section -->
<section class="bg-white py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-6">
                LegitBooks solves small business accounting
            </h1>
            <p class="text-xl text-gray-600 mb-8 max-w-3xl mx-auto">
                Simple, accurate cloud accounting designed for small businesses. Track invoices, manage payments, and stay compliant with ease.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('tenant.auth.register') }}" class="inline-flex items-center px-8 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white" style="background-color: var(--brand-primary);">
                    Start free trial
                </a>
                <a href="{{ route('marketing.pricing') }}" class="inline-flex items-center px-8 py-3 border border-gray-300 text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    View pricing
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Social Proof -->
<section class="bg-gray-50 py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <p class="text-center text-sm text-gray-500 mb-8">Trusted by small businesses across Kenya</p>
        <div class="flex justify-center items-center space-x-12 opacity-60">
            <div class="text-gray-400 font-semibold text-lg">Small Business</div>
            <div class="text-gray-400 font-semibold text-lg">Freelancers</div>
            <div class="text-gray-400 font-semibold text-lg">SMEs</div>
            <div class="text-gray-400 font-semibold text-lg">Accountants</div>
        </div>
    </div>
</section>

<!-- Feature Highlights -->
<section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-center text-gray-900 mb-12">Everything you need to manage your finances</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            <div class="text-center">
                <div class="bg-indigo-100 rounded-lg p-6 mb-4 inline-block">
                    <svg class="h-12 w-12 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Invoicing</h3>
                <p class="text-gray-600">Create professional invoices, track payments, and send reminders automatically.</p>
            </div>
            
            <div class="text-center">
                <div class="bg-green-100 rounded-lg p-6 mb-4 inline-block">
                    <svg class="h-12 w-12 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">M-Pesa Payments</h3>
                <p class="text-gray-600">Accept payments via M-Pesa and automatically reconcile transactions.</p>
            </div>
            
            <div class="text-center">
                <div class="bg-blue-100 rounded-lg p-6 mb-4 inline-block">
                    <svg class="h-12 w-12 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Reports & Dashboards</h3>
                <p class="text-gray-600">Get insights with real-time financial reports and visual dashboards.</p>
            </div>
            
            <div class="text-center">
                <div class="bg-purple-100 rounded-lg p-6 mb-4 inline-block">
                    <svg class="h-12 w-12 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Double-Entry Bookkeeping</h3>
                <p class="text-gray-600">Accurate accounting with automatic journal entries and audit trails.</p>
            </div>
        </div>
    </div>
</section>

<!-- How It Works -->
<section class="py-20 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-center text-gray-900 mb-12">How it works</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="text-center">
                <div class="bg-white rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4 shadow-md">
                    <span class="text-2xl font-bold" style="color: var(--brand-primary);">1</span>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Sign up</h3>
                <p class="text-gray-600">Create your account in minutes. No credit card required for the free trial.</p>
            </div>
            
            <div class="text-center">
                <div class="bg-white rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4 shadow-md">
                    <span class="text-2xl font-bold" style="color: var(--brand-primary);">2</span>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Connect</h3>
                <p class="text-gray-600">Import your contacts, products, and chart of accounts. Set up M-Pesa integration.</p>
            </div>
            
            <div class="text-center">
                <div class="bg-white rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4 shadow-md">
                    <span class="text-2xl font-bold" style="color: var(--brand-primary);">3</span>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Stay compliant</h3>
                <p class="text-gray-600">Generate invoices, track payments, and maintain accurate records automatically.</p>
            </div>
        </div>
    </div>
</section>

<!-- Why LegitBooks -->
<section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <div>
                <h2 class="text-3xl font-bold text-gray-900 mb-6">Why choose LegitBooks over spreadsheets?</h2>
                <ul class="space-y-4">
                    <li class="flex items-start">
                        <svg class="h-6 w-6 text-green-500 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-gray-700"><strong>Automatic calculations:</strong> No more manual formula errors. LegitBooks handles all calculations automatically.</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="h-6 w-6 text-green-500 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-gray-700"><strong>Real-time collaboration:</strong> Multiple team members can work simultaneously with proper access controls.</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="h-6 w-6 text-green-500 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-gray-700"><strong>M-Pesa integration:</strong> Accept payments directly and reconcile automatically.</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="h-6 w-6 text-green-500 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-gray-700"><strong>Audit trail:</strong> Complete history of all changes for compliance and peace of mind.</span>
                    </li>
                </ul>
            </div>
            <div class="bg-gray-50 rounded-lg p-8">
                <div class="text-center">
                    <svg class="h-24 w-24 mx-auto text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <p class="text-gray-600 italic">"LegitBooks transformed how we manage our finances. No more spreadsheet nightmares!"</p>
                    <p class="text-sm text-gray-500 mt-4">— Small Business Owner</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Final CTA -->
<section class="py-20 bg-gray-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl font-bold text-white mb-4">Ready to get started?</h2>
        <p class="text-xl text-gray-300 mb-8">Start your free trial today. No credit card required.</p>
        <a href="{{ route('tenant.auth.register') }}" class="inline-flex items-center px-8 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700">
            Start free trial
        </a>
    </div>
</section>
@endsection

