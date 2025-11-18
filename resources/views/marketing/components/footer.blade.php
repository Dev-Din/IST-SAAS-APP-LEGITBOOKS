<footer class="bg-gray-50 border-t border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div class="col-span-1">
                <h3 class="text-lg font-bold mb-4" style="color: var(--brand-primary);">LegitBooks</h3>
                <p class="text-sm text-gray-600">Simple, accurate cloud accounting for small businesses.</p>
            </div>
            
            <div>
                <h4 class="text-sm font-semibold text-gray-900 uppercase tracking-wider mb-4">Product</h4>
                <ul class="space-y-2">
                    <li><a href="{{ route('marketing.features') }}" class="text-sm text-gray-600 hover:text-gray-900">Features</a></li>
                    <li><a href="{{ route('marketing.pricing') }}" class="text-sm text-gray-600 hover:text-gray-900">Pricing</a></li>
                    <li><a href="{{ route('marketing.solutions') }}" class="text-sm text-gray-600 hover:text-gray-900">Solutions</a></li>
                    <li><a href="{{ route('marketing.faq') }}" class="text-sm text-gray-600 hover:text-gray-900">FAQ</a></li>
                </ul>
            </div>
            
            <div>
                <h4 class="text-sm font-semibold text-gray-900 uppercase tracking-wider mb-4">Company</h4>
                <ul class="space-y-2">
                    <li><a href="{{ route('marketing.about') }}" class="text-sm text-gray-600 hover:text-gray-900">About</a></li>
                    <li><a href="{{ route('marketing.contact') }}" class="text-sm text-gray-600 hover:text-gray-900">Contact</a></li>
                    <li><a href="{{ route('marketing.legal.terms') }}" class="text-sm text-gray-600 hover:text-gray-900">Terms</a></li>
                    <li><a href="{{ route('marketing.legal.privacy') }}" class="text-sm text-gray-600 hover:text-gray-900">Privacy</a></li>
                </ul>
            </div>
            
            <div>
                <h4 class="text-sm font-semibold text-gray-900 uppercase tracking-wider mb-4">Account</h4>
                <ul class="space-y-2">
                    <li><a href="{{ route('tenant.auth.login') }}" class="text-sm text-gray-600 hover:text-gray-900">Sign in</a></li>
                    <li><a href="{{ route('tenant.auth.register') }}" class="text-sm text-gray-600 hover:text-gray-900">Start free trial</a></li>
                    <li><a href="{{ route('admin.login') }}" class="text-sm text-gray-600 hover:text-gray-900">Admin login</a></li>
                </ul>
            </div>
        </div>
        
        <div class="mt-8 pt-8 border-t border-gray-200">
            <p class="text-center text-sm text-gray-600">
                &copy; {{ date('Y') }} LegitBooks. All rights reserved.
            </p>
        </div>
    </div>
</footer>

