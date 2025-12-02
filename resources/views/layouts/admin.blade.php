<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'LegitBooks Admin')</title>
    <link rel="icon" type="image/png" href="{{ asset('LegitBooks-tab-logo.png') }}" id="favicon">
    <link rel="apple-touch-icon" href="{{ asset('LegitBooks-tab-logo.png') }}">
    <link rel="mask-icon" href="{{ asset('LegitBooks-tab-logo.png') }}" color="#1e293b">
    @vite(['resources/css/admin.css', 'resources/js/app.js'])
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
    <div class="min-h-screen w-full max-w-full {{ request()->is('admin/login') ? '' : 'flex' }}">
        @auth('admin')
        <!-- Sidebar -->
        <aside class="hidden md:flex w-64 bg-white shadow-lg flex-col h-screen fixed left-0 top-0 z-50">
            <!-- Header -->
            <div class="text-white px-4 py-4 flex items-center space-x-2" style="background-color: #392a26;">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <span class="text-lg font-semibold">LegitBooks Admin</span>
            </div>
            
            <!-- Navigation Menu -->
            <nav class="flex-1 px-4 py-4 space-y-1 overflow-y-auto">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center space-x-3 px-3 py-2 rounded-md {{ request()->routeIs('admin.dashboard') ? 'text-white border-l-4' : 'text-gray-700 hover:bg-gray-50' }}" style="{{ request()->routeIs('admin.dashboard') ? 'background-color: #392a26; border-color: #392a26;' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    <span class="font-medium">Dashboard</span>
                </a>
                
                <a href="{{ route('admin.reports.index') }}" class="flex items-center space-x-3 px-3 py-2 rounded-md {{ request()->routeIs('admin.reports.*') ? 'text-white border-l-4' : 'text-gray-700 hover:bg-gray-50' }}" style="{{ request()->routeIs('admin.reports.*') ? 'background-color: #392a26; border-color: #392a26;' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    <span class="font-medium">Reports</span>
                </a>
                
                <a href="{{ route('admin.tenants.index') }}" class="flex items-center space-x-3 px-3 py-2 rounded-md {{ request()->routeIs('admin.tenants.*') ? 'text-white border-l-4' : 'text-gray-700 hover:bg-gray-50' }}" style="{{ request()->routeIs('admin.tenants.*') ? 'background-color: #392a26; border-color: #392a26;' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                    <span class="font-medium">Tenants</span>
                </a>
                
                @if(auth('admin')->user()?->hasRole('owner'))
                <a href="{{ route('admin.admins.index') }}" class="flex items-center space-x-3 px-3 py-2 rounded-md {{ request()->routeIs('admin.admins.*') ? 'text-white border-l-4' : 'text-gray-700 hover:bg-gray-50' }}" style="{{ request()->routeIs('admin.admins.*') ? 'background-color: #392a26; border-color: #392a26;' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    <span class="font-medium">Admins</span>
                </a>
                
                <a href="{{ route('admin.settings.index') }}" class="flex items-center space-x-3 px-3 py-2 rounded-md {{ request()->routeIs('admin.settings.*') ? 'text-white border-l-4' : 'text-gray-700 hover:bg-gray-50' }}" style="{{ request()->routeIs('admin.settings.*') ? 'background-color: #392a26; border-color: #392a26;' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <span class="font-medium">Settings</span>
                </a>
                @endif
                
                <!-- Profile Link (visible to all admins) -->
                <a href="{{ route('admin.profile.index') }}" class="flex items-center space-x-3 px-3 py-2 rounded-md {{ request()->routeIs('admin.profile.*') ? 'text-white border-l-4' : 'text-gray-700 hover:bg-gray-50' }}" style="{{ request()->routeIs('admin.profile.*') ? 'background-color: #392a26; border-color: #392a26;' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    <span class="font-medium">Profile</span>
                </a>
            </nav>
            
            <!-- Logout Button -->
            <div class="px-4 py-4 border-t border-gray-200">
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center space-x-3 px-3 py-2 rounded-md text-red-600 hover:bg-red-50 border-l-4 border-red-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                        <span class="font-medium">Logout</span>
                    </button>
                </form>
            </div>
        </aside>
        
        <!-- Mobile Menu Button (for mobile view) -->
        <div class="md:hidden fixed top-4 left-4 z-50">
            <button type="button" class="inline-flex items-center justify-center p-2 rounded-md text-gray-700 bg-white shadow-md hover:bg-gray-50 focus:outline-none" id="admin-mobile-menu-button">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
        </div>
        
        <!-- Mobile Sidebar -->
        <div class="md:hidden fixed inset-0 z-40 hidden" id="admin-mobile-sidebar">
            <div class="fixed inset-0 bg-gray-600 bg-opacity-75" onclick="document.getElementById('admin-mobile-sidebar').classList.add('hidden')"></div>
            <aside class="fixed top-0 left-0 bottom-0 w-64 bg-white shadow-xl transform transition-transform">
                <div class="flex flex-col h-full">
                    <!-- Header -->
                    <div class="text-white px-4 py-4 flex items-center justify-between" style="background-color: #392a26;">
                        <div class="flex items-center space-x-2">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <span class="text-lg font-semibold">LegitBooks Admin</span>
                        </div>
                        <button onclick="document.getElementById('admin-mobile-sidebar').classList.add('hidden')" class="text-white hover:text-gray-200">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <!-- Navigation Menu -->
                    <nav class="flex-1 px-4 py-4 space-y-1 overflow-y-auto">
                        <a href="{{ route('admin.dashboard') }}" class="flex items-center space-x-3 px-3 py-2 rounded-md {{ request()->routeIs('admin.dashboard') ? 'text-white border-l-4' : 'text-gray-700 hover:bg-gray-50' }}" style="{{ request()->routeIs('admin.dashboard') ? 'background-color: #392a26; border-color: #392a26;' : '' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                            </svg>
                            <span class="font-medium">Dashboard</span>
                        </a>
                        
                        <a href="{{ route('admin.reports.index') }}" class="flex items-center space-x-3 px-3 py-2 rounded-md {{ request()->routeIs('admin.reports.*') ? 'text-white border-l-4' : 'text-gray-700 hover:bg-gray-50' }}" style="{{ request()->routeIs('admin.reports.*') ? 'background-color: #392a26; border-color: #392a26;' : '' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            <span class="font-medium">Reports</span>
                        </a>
                        
                        <a href="{{ route('admin.tenants.index') }}" class="flex items-center space-x-3 px-3 py-2 rounded-md {{ request()->routeIs('admin.tenants.*') ? 'text-white border-l-4' : 'text-gray-700 hover:bg-gray-50' }}" style="{{ request()->routeIs('admin.tenants.*') ? 'background-color: #392a26; border-color: #392a26;' : '' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                            <span class="font-medium">Tenants</span>
                        </a>
                        
                        @if(auth('admin')->user()?->hasRole('owner'))
                        <a href="{{ route('admin.admins.index') }}" class="flex items-center space-x-3 px-3 py-2 rounded-md {{ request()->routeIs('admin.admins.*') ? 'text-white border-l-4' : 'text-gray-700 hover:bg-gray-50' }}" style="{{ request()->routeIs('admin.admins.*') ? 'background-color: #392a26; border-color: #392a26;' : '' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                            <span class="font-medium">Admins</span>
                        </a>
                        
                        <a href="{{ route('admin.settings.index') }}" class="flex items-center space-x-3 px-3 py-2 rounded-md {{ request()->routeIs('admin.settings.*') ? 'text-white border-l-4' : 'text-gray-700 hover:bg-gray-50' }}" style="{{ request()->routeIs('admin.settings.*') ? 'background-color: #392a26; border-color: #392a26;' : '' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <span class="font-medium">Settings</span>
                        </a>
                        @endif
                        
                        <!-- Profile Link (visible to all admins) -->
                        <a href="{{ route('admin.profile.index') }}" class="flex items-center space-x-3 px-3 py-2 rounded-md {{ request()->routeIs('admin.profile.*') ? 'text-white border-l-4' : 'text-gray-700 hover:bg-gray-50' }}" style="{{ request()->routeIs('admin.profile.*') ? 'background-color: #392a26; border-color: #392a26;' : '' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            <span class="font-medium">Profile</span>
                        </a>
                    </nav>
                    
                    <!-- Logout Button -->
                    <div class="px-4 py-4 border-t border-gray-200">
                        <form method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                            <button type="submit" class="w-full flex items-center space-x-3 px-3 py-2 rounded-md text-red-600 hover:bg-red-50 border-l-4 border-red-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                </svg>
                                <span class="font-medium">Logout</span>
                            </button>
                        </form>
                    </div>
                </div>
            </aside>
        </div>
        
        <script>
        document.getElementById('admin-mobile-menu-button')?.addEventListener('click', function() {
            const sidebar = document.getElementById('admin-mobile-sidebar');
            sidebar.classList.toggle('hidden');
        });
        </script>
        @endauth

        <!-- Main Content Area -->
        @if(request()->is('admin/login'))
        <main class="min-h-screen flex items-center justify-center px-4 sm:px-6">
            @if(session('success'))
                <div class="absolute top-4 left-1/2 transform -translate-x-1/2 w-full max-w-md px-4">
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                        {{ session('success') }}
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="absolute top-4 left-1/2 transform -translate-x-1/2 w-full max-w-md px-4">
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                        {{ session('error') }}
                    </div>
                </div>
            @endif

            @if($errors->any())
                <div class="absolute top-4 left-1/2 transform -translate-x-1/2 w-full max-w-md px-4">
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                        <ul>
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <div class="w-full max-w-md">
                @yield('content')
            </div>
        </main>
        @else
        <main class="flex-1 ml-0 md:ml-64 py-6 overflow-x-hidden">
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

            @if($errors->any())
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-4">
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                        <ul>
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                @yield('content')
            </div>
        </main>
        @endif
    </div>
</body>
</html>

