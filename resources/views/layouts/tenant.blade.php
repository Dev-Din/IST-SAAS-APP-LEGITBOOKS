<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'LegitBooks')</title>
    @php
        $tenant = app(\App\Services\TenantContext::class)->getTenant();
        $brandMode = $tenant ? $tenant->getBrandingMode() : env('BRANDING_MODE', 'A');
        $brandSettings = $tenant ? $tenant->getBrandSettings() : [];
        $brandColor = $brandSettings['primary_color'] ?? '#392a26';
    @endphp
    <link rel="icon" type="image/png" href="{{ asset('LegitBooks-tab-logo.png') }}" id="favicon">
    <link rel="apple-touch-icon" href="{{ asset('LegitBooks-tab-logo.png') }}">
    <link rel="mask-icon" href="{{ asset('LegitBooks-tab-logo.png') }}" color="{{ $brandColor }}">
    @vite(['resources/css/tenant.css', 'resources/js/app.js'])
    <style>
        :root {
            --brand-primary: {{ $brandSettings['primary_color'] ?? '#392a26' }};
            --brand-text: {{ $brandSettings['text_color'] ?? '#ffffff' }};
        }
    </style>
    <script>
        // Create rounded favicon
        (function() {
            const img = new Image();
            img.onload = function() {
                try {
                    const canvas = document.createElement('canvas');
                    const size = 32; // Standard favicon size
                    canvas.width = size;
                    canvas.height = size;
                    const ctx = canvas.getContext('2d');
                    
                    // Create circular clipping path
                    ctx.beginPath();
                    ctx.arc(size / 2, size / 2, size / 2, 0, 2 * Math.PI);
                    ctx.clip();
                    
                    // Draw the image
                    ctx.drawImage(img, 0, 0, size, size);
                    
                    // Update favicon
                    const favicon = document.getElementById('favicon');
                    if (favicon) {
                        favicon.href = canvas.toDataURL('image/png');
                    } else {
                        // Create new link if not found
                        const link = document.createElement('link');
                        link.rel = 'icon';
                        link.type = 'image/png';
                        link.href = canvas.toDataURL('image/png');
                        document.head.appendChild(link);
                    }
                } catch (e) {
                    console.warn('Failed to create rounded favicon:', e);
                }
            };
            img.onerror = function() {
                console.warn('Failed to load favicon image');
            };
            img.src = '{{ asset("LegitBooks-tab-logo.png") }}';
        })();
    </script>
</head>
<body class="bg-gray-50 overflow-x-hidden">
    <div class="min-h-screen w-full max-w-full">
        @php
            $isAuthPage = request()->routeIs('tenant.auth.login') || request()->routeIs('tenant.auth.register') || request()->routeIs('tenant.auth.billing');
        @endphp
        
        @if($isAuthPage)
        {{-- Auth pages: Centered logo only, no nav links --}}
        <nav class="bg-white shadow-sm border-b w-full overflow-x-hidden">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
                <div class="flex justify-center items-center h-16">
                    <div class="flex items-center">
                        <a href="{{ route('marketing.home') }}" class="text-lg sm:text-xl font-bold hover:opacity-80 transition-opacity" style="color: var(--brand-primary);">LegitBooks</a>
                    </div>
                </div>
            </div>
        </nav>
        @else
        {{-- Authenticated pages: Full nav with links --}}
        @auth
        <nav class="bg-white shadow-sm border-b w-full overflow-x-hidden">
            <div class="w-full max-w-full">
                <!-- Desktop Layout -->
                <div class="hidden md:flex justify-between items-center h-16 w-full max-w-full">
                    <!-- Logo - Far Left -->
                    <div class="flex items-center flex-shrink-0 pl-4 md:pl-8">
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
                            @perm('view_dashboard')
                            <a href="{{ route('tenant.dashboard') }}" class="text-gray-700 hover:text-gray-900 font-medium py-2 {{ request()->routeIs('tenant.dashboard') ? 'text-gray-900' : '' }}">Dashboard</a>
                            @endperm
                            @anyperm(['manage_invoices', 'view_invoices'])
                            <a href="{{ route('tenant.invoices.index') }}" class="text-gray-700 hover:text-gray-900 font-medium py-2 {{ request()->routeIs('tenant.invoices.*') ? 'text-gray-900' : '' }}">Invoices</a>
                            @endanyperm
                            @anyperm(['manage_contacts', 'view_contacts'])
                            <a href="{{ route('tenant.contacts.index') }}" class="text-gray-700 hover:text-gray-900 font-medium py-2 {{ request()->routeIs('tenant.contacts.*') ? 'text-gray-900' : '' }}">Contacts</a>
                            @endanyperm
                            @perm('manage_users')
                            @php $isFree = $tenant ? $tenant->onFreePlan() : false; @endphp
                            @if(!$isFree)
                                <a href="{{ route('tenant.users.index') }}" class="text-gray-700 hover:text-gray-900 font-medium py-2 {{ request()->routeIs('tenant.users.*') || request()->routeIs('tenant.invitations.*') ? 'text-gray-900' : '' }}">Users</a>
                            @else
                                <button
                                    type="button"
                                    class="text-gray-700 font-medium py-2 opacity-60 cursor-not-allowed"
                                    aria-disabled="true"
                                    tabindex="-1"
                                    title="Invite users available on paid plans - Upgrade now to add team members."
                                    onclick="openBillingModal()"
                                >
                                    Users
                                </button>
                            @endif
                            @endperm
                            @perm('manage_billing')
                            <a href="{{ route('tenant.billing.index') }}" class="text-gray-700 hover:text-gray-900 font-medium py-2 {{ request()->routeIs('tenant.billing.*') ? 'text-gray-900' : '' }}">Billing & Subscriptions</a>
                            @endperm
                        </nav>
                    </div>
                    
                    <!-- Logout Button - Far Right -->
                    <div class="flex items-center flex-shrink-0 pr-4 md:pr-8">
                        <form method="POST" action="{{ route('tenant.auth.logout') }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white shadow-sm" style="background-color: var(--brand-primary);">
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Mobile Layout -->
                <div class="md:hidden flex justify-between items-center h-16 w-full max-w-full px-3 sm:px-4">
                    <div class="flex items-center flex-shrink-0">
                        <a href="{{ route('tenant.dashboard') }}" class="flex items-center">
                            @if($brandMode === 'B')
                                <span class="text-lg font-bold text-gray-900">LegitBooks</span>
                            @else
                                <span class="text-lg font-bold" style="color: var(--brand-primary);">
                                    {{ $brandSettings['name'] ?? ($tenant->name ?? 'LegitBooks') }}
                                </span>
                            @endif
                        </a>
                    </div>
                    <button type="button" class="inline-flex items-center justify-center p-2 rounded-md text-gray-700 hover:text-gray-900 focus:outline-none flex-shrink-0" id="tenant-mobile-menu-button">
                        <svg class="h-5 w-5 sm:h-6 sm:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                </div>
                
                <!-- Mobile menu -->
                <div class="md:hidden hidden w-full" id="tenant-mobile-menu">
                    <div class="px-3 pt-2 pb-3 space-y-1 bg-white border-t w-full max-w-full">
                        @perm('view_dashboard')
                        <a href="{{ route('tenant.dashboard') }}" class="block px-3 py-2 text-gray-700 hover:text-gray-900">Dashboard</a>
                        @endperm
                        @anyperm(['manage_invoices', 'view_invoices'])
                        <a href="{{ route('tenant.invoices.index') }}" class="block px-3 py-2 text-gray-700 hover:text-gray-900">Invoices</a>
                        @endanyperm
                        @anyperm(['manage_contacts', 'view_contacts'])
                        <a href="{{ route('tenant.contacts.index') }}" class="block px-3 py-2 text-gray-700 hover:text-gray-900">Contacts</a>
                        @endanyperm
                        @perm('manage_users')
                        @php $isFree = $tenant ? $tenant->onFreePlan() : false; @endphp
                        @if(!$isFree)
                            <a href="{{ route('tenant.users.index') }}" class="block px-3 py-2 text-gray-700 hover:text-gray-900">Users</a>
                        @else
                            <button
                                type="button"
                                class="block w-full text-left px-3 py-2 text-gray-700 opacity-60 cursor-not-allowed"
                                aria-disabled="true"
                                tabindex="-1"
                                title="Invite users available on paid plans - Upgrade now to add team members."
                                onclick="openBillingModal()"
                            >
                                Users
                            </button>
                        @endif
                        @endperm
                        @perm('manage_billing')
                        <a href="{{ route('tenant.billing.index') }}" class="block px-3 py-2 text-gray-700 hover:text-gray-900">Billing & Subscriptions</a>
                        @endperm
                        <form method="POST" action="{{ route('tenant.auth.logout') }}">
                            @csrf
                            <button type="submit" class="block w-full text-left px-3 py-2 text-gray-700 hover:text-gray-900">Logout</button>
                        </form>
                    </div>
                </div>
            </div>
        </nav>
        <script>
        document.getElementById('tenant-mobile-menu-button')?.addEventListener('click', function() {
            const menu = document.getElementById('tenant-mobile-menu');
            menu.classList.toggle('hidden');
        });

        function openBillingModal() {
            // Redirect to billing page if JS is disabled, otherwise could open modal
            window.location.href = '{{ route("tenant.billing.page") }}';
        }
        </script>
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

