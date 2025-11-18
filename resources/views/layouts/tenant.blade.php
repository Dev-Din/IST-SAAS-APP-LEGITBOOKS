<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'LegitBooks')</title>
    @vite(['resources/css/tenant.css', 'resources/js/app.js'])
    @php
        $tenant = app(\App\Services\TenantContext::class)->getTenant();
        $brandMode = $tenant ? $tenant->getBrandingMode() : env('BRANDING_MODE', 'A');
        $brandSettings = $tenant ? $tenant->getBrandSettings() : [];
    @endphp
    <style>
        :root {
            --brand-primary: {{ $brandSettings['primary_color'] ?? '#392a26' }};
            --brand-text: {{ $brandSettings['text_color'] ?? '#ffffff' }};
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        @php
            $isAuthPage = request()->routeIs('tenant.auth.login') || request()->routeIs('tenant.auth.register') || request()->routeIs('tenant.auth.billing');
        @endphp
        
        @if($isAuthPage)
        {{-- Auth pages: Centered logo only, no nav links --}}
        <nav class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-center items-center h-16">
                    <div class="flex items-center">
                        <span class="text-xl font-bold" style="color: var(--brand-primary);">LegitBooks</span>
                    </div>
                </div>
            </div>
        </nav>
        @else
        {{-- Authenticated pages: Full nav with links --}}
        @auth
        <nav class="bg-white shadow-sm border-b w-full">
            <div class="w-full">
                <div class="flex justify-between items-center h-16 w-full">
                    <!-- Logo - Far Left -->
                    <div class="flex items-center flex-shrink-0 pl-8">
                        <a href="{{ route('tenant.dashboard') }}" class="flex items-center py-2">
                            @if($brandMode === 'B')
                                <span class="text-xl font-bold text-gray-900">LegitBooks</span>
                            @else
                                <span class="text-xl font-bold" style="color: var(--brand-primary);">
                                    {{ $brandSettings['name'] ?? ($tenant->name ?? 'LegitBooks') }}
                                </span>
                            @endif
                        </a>
                    </div>
                    
                    <!-- Centered Navigation Links -->
                    <div class="flex items-center justify-center flex-1">
                        <nav class="flex items-center space-x-8">
                            <a href="{{ route('tenant.dashboard') }}" class="text-gray-700 hover:text-gray-900 font-medium py-2 {{ request()->routeIs('tenant.dashboard') ? 'text-gray-900' : '' }}">Dashboard</a>
                            <a href="{{ route('tenant.invoices.index') }}" class="text-gray-700 hover:text-gray-900 font-medium py-2 {{ request()->routeIs('tenant.invoices.*') ? 'text-gray-900' : '' }}">Invoices</a>
                            <a href="{{ route('tenant.contacts.index') }}" class="text-gray-700 hover:text-gray-900 font-medium py-2 {{ request()->routeIs('tenant.contacts.*') ? 'text-gray-900' : '' }}">Contacts</a>
                            <a href="{{ route('tenant.billing.index') }}" class="text-gray-700 hover:text-gray-900 font-medium py-2 {{ request()->routeIs('tenant.billing.*') ? 'text-gray-900' : '' }}">Billing & Subscriptions</a>
                        </nav>
                    </div>
                    
                    <!-- Logout Button - Far Right -->
                    <div class="flex items-center flex-shrink-0 pr-8">
                        <form method="POST" action="{{ route('tenant.auth.logout') }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white shadow-sm" style="background-color: var(--brand-primary);">
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </nav>
        @endauth
        @endif

        <main class="py-6">
            @if(session('success'))
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-4">
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                        {{ session('success') }}
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-4">
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                        {{ session('error') }}
                    </div>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</body>
</html>

