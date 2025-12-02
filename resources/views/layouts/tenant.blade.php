<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $tenant = app(\App\Services\TenantContext::class)->getTenant();
        $brandMode = $tenant ? $tenant->getBrandingMode() : env('BRANDING_MODE', 'A');
        $brandSettings = $tenant ? $tenant->getBrandSettings() : [];
        $brandColor = $brandSettings['primary_color'] ?? '#392a26';
        $brandName = $brandSettings['name'] ?? ($tenant->name ?? 'LegitBooks');
        $pageTitle = $brandMode === 'C' ? $brandName : 'LegitBooks';
        $logoPath = ($brandMode === 'C' && !empty($brandSettings['logo_path'])) ? $brandSettings['logo_path'] : 'LegitBooks-tab-logo.png';
    @endphp
    <title>@yield('title', $pageTitle)</title>
    <link rel="icon" type="image/png" href="{{ asset($logoPath) }}" id="favicon">
    <link rel="apple-touch-icon" href="{{ asset($logoPath) }}">
    <link rel="mask-icon" href="{{ asset($logoPath) }}" color="{{ $brandColor }}">
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
            img.src = '{{ asset($logoPath) }}';
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
                        @if($brandMode === 'C')
                            <span class="text-lg sm:text-xl font-bold" style="color: var(--brand-primary);">{{ $brandName }}</span>
                        @else
                            <a href="{{ route('marketing.home') }}" class="text-lg sm:text-xl font-bold hover:opacity-80 transition-opacity" style="color: var(--brand-primary);">LegitBooks</a>
                        @endif
                    </div>
                </div>
            </div>
        </nav>
        @else
        {{-- Authenticated pages: Full nav with links --}}
        @auth
        <nav class="bg-white shadow-sm border-b w-full overflow-visible relative z-50">
            <div class="w-full max-w-full overflow-visible">
                <!-- Desktop Layout -->
                <div class="hidden md:flex justify-between items-center h-16 w-full max-w-full overflow-visible">
                    <!-- Tenant Name Dropdown - Far Left -->
                    <div class="flex items-center flex-shrink-0 pl-4 md:pl-8 relative z-[100]" style="overflow: visible;">
                        <div class="relative">
                            <button 
                                onclick="toggleTenantDropdown()"
                                class="flex items-center py-2 text-xl font-bold hover:opacity-80 transition-opacity focus:outline-none"
                                style="color: var(--brand-primary);"
                                id="tenant-dropdown-button"
                            >
                                @if($brandMode === 'B')
                                    <span class="text-gray-900">LegitBooks</span>
                                @else
                                    <span>{{ $brandSettings['name'] ?? ($tenant->name ?? 'LegitBooks') }}</span>
                                @endif
                                <svg class="ml-2 h-4 w-4 transition-transform" id="tenant-dropdown-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            
                            <!-- Dropdown Menu - Positioned below nav bar -->
                            <div 
                                id="tenant-dropdown-menu"
                                class="hidden absolute left-0 top-full mt-2 w-64 rounded-md shadow-xl bg-white border border-gray-200 z-[100]"
                                style="box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);"
                            >
                                <div class="py-2">
                                    <!-- Current Tenant -->
                                    <a href="{{ route('tenant.profile.index') }}" class="px-4 py-2 flex items-center space-x-3 hover:bg-gray-50 cursor-pointer">
                                        <div class="w-8 h-8 rounded flex items-center justify-center text-white text-xs font-semibold" style="background-color: var(--brand-primary);">
                                            {{ strtoupper(substr($brandSettings['name'] ?? ($tenant->name ?? 'LB'), 0, 2)) }}
                                        </div>
                                        <div class="flex-1">
                                            <div class="text-sm font-semibold text-gray-900">{{ $brandSettings['name'] ?? ($tenant->name ?? 'LegitBooks') }}</div>
                                        </div>
                                    </a>
                                    
                                    <div class="border-t border-gray-200 my-1"></div>
                                    
                                    @perm('manage_billing')
                                    <a href="{{ route('tenant.billing.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 {{ request()->routeIs('tenant.billing.*') ? 'bg-gray-50 font-medium' : '' }}">
                                        <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        Subscription and billing
                                    </a>
                                    @endperm
                                </div>
                            </div>
                        </div>
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
                    <div class="flex items-center flex-shrink-0 relative">
                        <div class="relative">
                            <button 
                                onclick="toggleTenantDropdownMobile()"
                                class="flex items-center text-lg font-bold hover:opacity-80 transition-opacity focus:outline-none"
                                style="color: var(--brand-primary);"
                                id="tenant-dropdown-button-mobile"
                            >
                                @if($brandMode === 'B')
                                    <span class="text-gray-900">LegitBooks</span>
                                @else
                                    <span>{{ $brandSettings['name'] ?? ($tenant->name ?? 'LegitBooks') }}</span>
                                @endif
                                <svg class="ml-2 h-4 w-4 transition-transform" id="tenant-dropdown-arrow-mobile" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            
                            <!-- Mobile Dropdown Menu - Positioned below nav bar -->
                            <div 
                                id="tenant-dropdown-menu-mobile"
                                class="hidden absolute left-0 top-full mt-2 w-64 rounded-md shadow-xl bg-white border border-gray-200 z-[100]"
                                style="box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);"
                            >
                                <div class="py-2">
                                    <!-- Current Tenant -->
                                    <a href="{{ route('tenant.profile.index') }}" class="px-4 py-2 flex items-center space-x-3 hover:bg-gray-50 cursor-pointer">
                                        <div class="w-8 h-8 rounded flex items-center justify-center text-white text-xs font-semibold" style="background-color: var(--brand-primary);">
                                            {{ strtoupper(substr($brandSettings['name'] ?? ($tenant->name ?? 'LB'), 0, 2)) }}
                                        </div>
                                        <div class="flex-1">
                                            <div class="text-sm font-semibold text-gray-900">{{ $brandSettings['name'] ?? ($tenant->name ?? 'LegitBooks') }}</div>
                                        </div>
                                    </a>
                                    
                                    <div class="border-t border-gray-200 my-1"></div>
                                    
                                    @perm('manage_billing')
                                    <a href="{{ route('tenant.billing.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 {{ request()->routeIs('tenant.billing.*') ? 'bg-gray-50 font-medium' : '' }}">
                                        <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        Subscription and billing
                                    </a>
                                    @endperm
                                </div>
                            </div>
                        </div>
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

        // Tenant dropdown toggle (Desktop)
        function toggleTenantDropdown() {
            const menu = document.getElementById('tenant-dropdown-menu');
            const arrow = document.getElementById('tenant-dropdown-arrow');
            if (menu && arrow) {
                menu.classList.toggle('hidden');
                arrow.classList.toggle('rotate-180');
            }
        }

        // Tenant dropdown toggle (Mobile)
        function toggleTenantDropdownMobile() {
            const menu = document.getElementById('tenant-dropdown-menu-mobile');
            const arrow = document.getElementById('tenant-dropdown-arrow-mobile');
            if (menu && arrow) {
                menu.classList.toggle('hidden');
                arrow.classList.toggle('rotate-180');
            }
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const desktopButton = document.getElementById('tenant-dropdown-button');
            const desktopMenu = document.getElementById('tenant-dropdown-menu');
            const mobileButton = document.getElementById('tenant-dropdown-button-mobile');
            const mobileMenu = document.getElementById('tenant-dropdown-menu-mobile');

            // Desktop dropdown
            if (desktopButton && desktopMenu && !desktopButton.contains(event.target) && !desktopMenu.contains(event.target)) {
                desktopMenu.classList.add('hidden');
                const arrow = document.getElementById('tenant-dropdown-arrow');
                if (arrow) arrow.classList.remove('rotate-180');
            }

            // Mobile dropdown
            if (mobileButton && mobileMenu && !mobileButton.contains(event.target) && !mobileMenu.contains(event.target)) {
                mobileMenu.classList.add('hidden');
                const arrow = document.getElementById('tenant-dropdown-arrow-mobile');
                if (arrow) arrow.classList.remove('rotate-180');
            }
        });
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

