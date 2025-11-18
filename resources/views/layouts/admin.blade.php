<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'LegitBooks Admin')</title>
    @vite(['resources/css/admin.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        @auth('admin')
        <nav class="bg-slate-900 text-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
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
            </div>
        </nav>
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

