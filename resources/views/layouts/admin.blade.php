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
    <div class="min-h-screen w-full max-w-full">
        @auth('admin')
        <nav class="bg-slate-900 text-white w-full overflow-x-hidden">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
                <!-- Desktop Layout -->
                <div class="hidden md:flex justify-between h-16">
                    <div class="flex items-center">
                        <a href="{{ route('admin.dashboard') }}" class="text-xl font-bold">LegitBooks Admin</a>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="{{ route('admin.tenants.index') }}" class="hover:text-gray-300">Tenants</a>
                        @if(auth('admin')->user()?->hasRole('superadmin'))
                            <a href="{{ route('admin.admins.index') }}" class="hover:text-gray-300">Admins</a>
                            <a href="{{ route('admin.settings.index') }}" class="hover:text-gray-300">Settings</a>
                        @endif
                        <form method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                            <button type="submit" class="hover:text-gray-300">Logout</button>
                        </form>
                    </div>
                </div>
                
                <!-- Mobile Layout -->
                <div class="md:hidden flex justify-between items-center h-16 px-3 sm:px-4">
                    <div class="flex items-center">
                        <a href="{{ route('admin.dashboard') }}" class="text-lg font-bold">LegitBooks Admin</a>
                    </div>
                    <button type="button" class="inline-flex items-center justify-center p-2 rounded-md text-white hover:text-gray-300 focus:outline-none flex-shrink-0" id="admin-mobile-menu-button">
                        <svg class="h-5 w-5 sm:h-6 sm:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                </div>
                
                <!-- Mobile menu -->
                <div class="md:hidden hidden w-full" id="admin-mobile-menu">
                    <div class="px-3 pt-2 pb-3 space-y-1 bg-slate-900 border-t border-slate-800 w-full">
                        <a href="{{ route('admin.tenants.index') }}" class="block px-3 py-2 text-white hover:text-gray-300">Tenants</a>
                        @if(auth('admin')->user()?->hasRole('superadmin'))
                            <a href="{{ route('admin.admins.index') }}" class="block px-3 py-2 text-white hover:text-gray-300">Admins</a>
                            <a href="{{ route('admin.settings.index') }}" class="block px-3 py-2 text-white hover:text-gray-300">Settings</a>
                        @endif
                        <form method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                            <button type="submit" class="block w-full text-left px-3 py-2 text-white hover:text-gray-300">Logout</button>
                        </form>
                    </div>
                </div>
            </div>
        </nav>
        <script>
        document.getElementById('admin-mobile-menu-button')?.addEventListener('click', function() {
            const menu = document.getElementById('admin-mobile-menu');
            menu.classList.toggle('hidden');
        });
        </script>
        @endauth

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

            @yield('content')
        </main>
    </div>
</body>
</html>

