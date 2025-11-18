@extends('layouts.marketing')

@section('title', 'Pricing - LegitBooks')

@section('content')
<!-- Hero -->
<section class="bg-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">Simple, transparent pricing</h1>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">Choose the plan that fits your business. All plans include a 14-day free trial.</p>
        </div>
    </div>
</section>

<!-- Pricing Cards -->
<section class="py-16 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            <!-- Free Plan -->
            <div class="bg-white rounded-lg shadow-lg p-8">
                <h3 class="text-2xl font-bold text-gray-900 mb-2">Free</h3>
                <div class="mb-6">
                    <span class="text-4xl font-bold text-gray-900">KSh 0</span>
                    <span class="text-gray-600">/month</span>
                </div>
                <ul class="space-y-4 mb-8">
                    <li class="flex items-start">
                        <svg class="h-5 w-5 text-green-500 mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-gray-600">Up to 50 invoices/month</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="h-5 w-5 text-green-500 mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-gray-600">1 user</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="h-5 w-5 text-green-500 mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-gray-600">Basic reporting</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="h-5 w-5 text-green-500 mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-gray-600">Email support</span>
                    </li>
                </ul>
                <a href="{{ route('tenant.auth.login') }}" class="block w-full text-center px-4 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Get started
                </a>
            </div>

            <!-- Starter Plan -->
            <div class="bg-white rounded-lg shadow-lg p-8 border-2" style="border-color: var(--brand-primary);">
                <div class="text-center mb-2">
                    <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full text-white" style="background-color: var(--brand-primary);">POPULAR</span>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-2">Starter</h3>
                <div class="mb-6">
                    <span class="text-4xl font-bold text-gray-900">KSh 2,500</span>
                    <span class="text-gray-600">/month</span>
                </div>
                <ul class="space-y-4 mb-8">
                    <li class="flex items-start">
                        <svg class="h-5 w-5 text-green-500 mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-gray-600">Unlimited invoices</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="h-5 w-5 text-green-500 mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-gray-600">Up to 3 users</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="h-5 w-5 text-green-500 mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-gray-600">Advanced reporting</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="h-5 w-5 text-green-500 mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-gray-600">M-Pesa integration</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="h-5 w-5 text-green-500 mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-gray-600">Priority support</span>
                    </li>
                </ul>
                <a href="{{ route('tenant.auth.register') }}" class="block w-full text-center px-4 py-2 border border-transparent rounded-md text-white shadow-sm" style="background-color: var(--brand-primary);">
                    Start free trial
                </a>
            </div>

            <!-- Business Plan -->
            <div class="bg-white rounded-lg shadow-lg p-8">
                <h3 class="text-2xl font-bold text-gray-900 mb-2">Business</h3>
                <div class="mb-6">
                    <span class="text-4xl font-bold text-gray-900">KSh 5,000</span>
                    <span class="text-gray-600">/month</span>
                </div>
                <ul class="space-y-4 mb-8">
                    <li class="flex items-start">
                        <svg class="h-5 w-5 text-green-500 mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-gray-600">Unlimited invoices</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="h-5 w-5 text-green-500 mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-gray-600">Up to 10 users</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="h-5 w-5 text-green-500 mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-gray-600">Custom reports</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="h-5 w-5 text-green-500 mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-gray-600">CSV import/export</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="h-5 w-5 text-green-500 mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-gray-600">24/7 support</span>
                    </li>
                </ul>
                <a href="{{ route('tenant.auth.register') }}" class="block w-full text-center px-4 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Start free trial
                </a>
            </div>

            <!-- Enterprise Plan -->
            <div class="bg-white rounded-lg shadow-lg p-8">
                <h3 class="text-2xl font-bold text-gray-900 mb-2">Enterprise</h3>
                <div class="mb-6">
                    <span class="text-4xl font-bold text-gray-900">Custom</span>
                </div>
                <ul class="space-y-4 mb-8">
                    <li class="flex items-start">
                        <svg class="h-5 w-5 text-green-500 mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-gray-600">Unlimited everything</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="h-5 w-5 text-green-500 mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-gray-600">Unlimited users</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="h-5 w-5 text-green-500 mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-gray-600">White-label options</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="h-5 w-5 text-green-500 mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-gray-600">API access</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="h-5 w-5 text-green-500 mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-gray-600">Dedicated account manager</span>
                    </li>
                </ul>
                <a href="{{ route('marketing.contact') }}" class="block w-full text-center px-4 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Contact sales
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Comparison Table -->
<section class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-center text-gray-900 mb-12">Compare plans</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Feature</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Free</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Starter</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Business</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Enterprise</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Invoices per month</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500">50</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500">Unlimited</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500">Unlimited</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500">Unlimited</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Users</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500">1</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500">3</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500">10</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500">Unlimited</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">M-Pesa Integration</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500">—</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-green-600">✓</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-green-600">✓</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-green-600">✓</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Advanced Reports</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500">—</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-green-600">✓</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-green-600">✓</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-green-600">✓</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">CSV Import/Export</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500">—</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500">—</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-green-600">✓</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-green-600">✓</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="py-16 bg-gray-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl font-bold text-white mb-4">Start your 14-day free trial</h2>
        <p class="text-xl text-gray-300 mb-8">No credit card required. Cancel anytime.</p>
        <a href="{{ route('tenant.auth.login') }}" class="inline-flex items-center px-8 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700">
            Get started now
        </a>
    </div>
</section>
@endsection

