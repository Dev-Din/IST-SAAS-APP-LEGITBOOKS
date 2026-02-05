@extends('layouts.tenant')

@section('title', 'Login')

@section('content')
<div class="max-w-md mx-auto mt-12 sm:mt-16 md:mt-20 px-4 sm:px-6 w-full">
    <div class="bg-white shadow-md rounded-lg px-6 sm:px-8 pt-6 pb-8 mb-4 w-full">
        @php
            $tenant = app(\App\Services\TenantContext::class)->getTenant();
            $brandMode = $tenant ? $tenant->getBrandingMode() : env('BRANDING_MODE', 'A');
            $brandSettings = $tenant ? $tenant->getBrandSettings() : [];
        @endphp
        
        @if($brandMode === 'B')
            <h2 class="text-2xl font-bold text-center mb-6">LegitBooks</h2>
        @elseif($brandMode === 'C')
            <h2 class="text-2xl font-bold text-center mb-6" style="color: var(--brand-primary);">
                {{ $brandSettings['name'] ?? ($tenant->name ?? 'Login') }}
            </h2>
        @else
            <h2 class="text-2xl font-bold text-center mb-6" style="color: var(--brand-primary);">
                {{ $brandSettings['name'] ?? ($tenant->name ?? 'Login') }}
            </h2>
        @endif
        
        <form method="POST" action="{{ route('tenant.auth.login') }}">
            @csrf

            @if($errors->any())
                <div class="mb-4 p-3 rounded bg-red-50 border border-red-200 text-red-700 text-sm">
                    {{ $errors->first('email') }}
                </div>
            @endif

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                    Email
                </label>
                <input
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('email') border-red-500 @enderror"
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    autocomplete="email"
                >
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                    Password
                </label>
                <input
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-1 leading-tight focus:outline-none focus:shadow-outline @error('email') border-red-500 @enderror"
                    id="password"
                    type="password"
                    name="password"
                    required
                    autocomplete="current-password"
                >
                <div class="mt-1 text-right">
                    <a href="{{ route('tenant.password.request') }}" class="text-sm hover:underline" style="color: var(--brand-primary);">Forgot password?</a>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <button
                    class="text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full"
                    style="background-color: var(--brand-primary);"
                    type="submit"
                >
                    Sign In
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

