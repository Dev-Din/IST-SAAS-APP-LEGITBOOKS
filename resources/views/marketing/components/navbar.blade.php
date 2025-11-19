<nav class="bg-white shadow-sm sticky top-0 z-50 w-full overflow-x-hidden">
    <div class="w-full max-w-full">
        <!-- Desktop Layout (md and above) -->
        <div class="hidden md:flex md:justify-between md:items-center h-16 w-full max-w-full">
            <!-- Logo - Far Left (minimal padding for readability) -->
            <div class="flex items-center flex-shrink-0 pl-4 md:pl-8">
                <a href="{{ route('marketing.home') }}" class="flex items-center py-2">
                    <span class="text-2xl font-bold" style="color: var(--brand-primary);">LegitBooks</span>
                </a>
            </div>
            
            <!-- Centered Navigation Links -->
            <div class="flex items-center justify-center flex-1">
                <nav class="flex items-center space-x-8">
                    <a href="{{ route('marketing.home') }}" class="text-gray-700 hover:text-gray-900 font-medium py-2 {{ request()->routeIs('marketing.home') ? 'text-gray-900' : '' }}">Home</a>
                    <a href="{{ route('marketing.features') }}" class="text-gray-700 hover:text-gray-900 font-medium py-2 {{ request()->routeIs('marketing.features') ? 'text-gray-900' : '' }}">Features</a>
                    <a href="{{ route('marketing.pricing') }}" class="text-gray-700 hover:text-gray-900 font-medium py-2 {{ request()->routeIs('marketing.pricing') ? 'text-gray-900' : '' }}">Pricing</a>
                    <a href="{{ route('marketing.solutions') }}" class="text-gray-700 hover:text-gray-900 font-medium py-2 {{ request()->routeIs('marketing.solutions') ? 'text-gray-900' : '' }}">Solutions</a>
                    <a href="{{ route('marketing.about') }}" class="text-gray-700 hover:text-gray-900 font-medium py-2 {{ request()->routeIs('marketing.about') ? 'text-gray-900' : '' }}">About</a>
                    <a href="{{ route('marketing.contact') }}" class="text-gray-700 hover:text-gray-900 font-medium py-2 {{ request()->routeIs('marketing.contact') ? 'text-gray-900' : '' }}">Contact</a>
                </nav>
            </div>
            
            <!-- Right side: Start Free Trial Button and Sign in - Far Right -->
            <div class="flex items-center flex-shrink-0 space-x-4 pr-4 md:pr-8">
                <a href="{{ route('tenant.auth.register') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white shadow-sm" style="background-color: var(--brand-primary);">
                    Start free trial
                </a>
                <a href="{{ route('tenant.auth.login') }}" class="text-gray-700 hover:text-gray-900 font-medium py-2">Sign in</a>
            </div>
        </div>

        <!-- Mobile Layout (below md) -->
        <div class="md:hidden flex justify-between items-center h-16 w-full max-w-full px-3 sm:px-4">
            <!-- Logo - Left -->
            <div class="flex items-center flex-shrink-0">
                <a href="{{ route('marketing.home') }}" class="flex items-center">
                    <span class="text-lg sm:text-xl font-bold" style="color: var(--brand-primary);">LegitBooks</span>
                </a>
            </div>
            
            <!-- Right side: Start free trial button, Sign in, and hamburger menu -->
            <div class="flex items-center space-x-2">
                <a href="{{ route('tenant.auth.register') }}" class="inline-flex items-center px-2 py-1.5 sm:px-3 sm:py-2 border border-transparent text-xs font-medium rounded-md text-white shadow-sm whitespace-nowrap" style="background-color: var(--brand-primary);">
                    Start free trial
                </a>
                <a href="{{ route('tenant.auth.login') }}" class="text-gray-700 hover:text-gray-900 font-medium text-xs sm:text-sm py-1.5 sm:py-2 whitespace-nowrap">Sign in</a>
                <button type="button" class="inline-flex items-center justify-center p-2 rounded-md text-gray-700 hover:text-gray-900 focus:outline-none flex-shrink-0" id="mobile-menu-button">
                    <svg class="h-5 w-5 sm:h-6 sm:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile menu -->
    <div class="md:hidden hidden w-full" id="mobile-menu">
        <div class="px-3 pt-2 pb-3 space-y-1 bg-white border-t w-full max-w-full">
            <a href="{{ route('marketing.home') }}" class="block px-3 py-2 text-gray-700 hover:text-gray-900">Home</a>
            <a href="{{ route('marketing.features') }}" class="block px-3 py-2 text-gray-700 hover:text-gray-900">Features</a>
            <a href="{{ route('marketing.pricing') }}" class="block px-3 py-2 text-gray-700 hover:text-gray-900">Pricing</a>
            <a href="{{ route('marketing.solutions') }}" class="block px-3 py-2 text-gray-700 hover:text-gray-900">Solutions</a>
            <a href="{{ route('marketing.about') }}" class="block px-3 py-2 text-gray-700 hover:text-gray-900">About</a>
            <a href="{{ route('marketing.contact') }}" class="block px-3 py-2 text-gray-700 hover:text-gray-900">Contact</a>
            <div class="border-t border-gray-200 my-2"></div>
            <a href="{{ route('tenant.auth.register') }}" class="block px-3 py-2 text-gray-700 hover:text-gray-900 font-medium">Start free trial</a>
            <a href="{{ route('tenant.auth.login') }}" class="block px-3 py-2 text-gray-700 hover:text-gray-900">Sign in</a>
        </div>
    </div>
</nav>

<script>
document.getElementById('mobile-menu-button')?.addEventListener('click', function() {
    const menu = document.getElementById('mobile-menu');
    menu.classList.toggle('hidden');
});
</script>

