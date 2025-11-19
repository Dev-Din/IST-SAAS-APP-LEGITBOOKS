<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'LegitBooks - Simple, Accurate Cloud Accounting')</title>
    <link rel="icon" type="image/png" href="{{ asset('LegitBooks-tab-logo.png') }}" id="favicon">
    <link rel="apple-touch-icon" href="{{ asset('LegitBooks-tab-logo.png') }}">
    <link rel="mask-icon" href="{{ asset('LegitBooks-tab-logo.png') }}" color="#392a26">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --brand-primary: #392a26;
            --brand-accent: #6366f1;
            --brand-accent-light: #818cf8;
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
<body class="bg-white text-gray-900 antialiased overflow-x-hidden">
    @include('marketing.components.navbar')
    
    <main>
        @if(session('success'))
            <div class="bg-green-50 border-l-4 border-green-400 p-4">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-green-700">{{ session('success') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-50 border-l-4 border-red-400 p-4">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700">{{ session('error') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @yield('content')
    </main>

    @include('marketing.components.footer')
</body>
</html>

