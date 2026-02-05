@extends('layouts.tenant')

@section('title', 'Forgot Password')

@section('content')
<div class="max-w-md mx-auto mt-12 sm:mt-16 md:mt-20 px-4 sm:px-6 w-full">
    <div class="bg-white shadow-md rounded-lg px-6 sm:px-8 pt-6 pb-8 mb-4 w-full">
        @php
            $tenant = app(\App\Services\TenantContext::class)->getTenant();
            $brandMode = $tenant ? $tenant->getBrandingMode() : env('BRANDING_MODE', 'A');
            $brandSettings = $tenant ? $tenant->getBrandSettings() : [];
        @endphp

        @if($brandMode === 'B')
            <h2 class="text-2xl font-bold text-center mb-6">Forgot Password</h2>
        @else
            <h2 class="text-2xl font-bold text-center mb-6" style="color: var(--brand-primary);">Forgot Password</h2>
        @endif

        @if(session('status'))
            <div class="mb-4 p-3 rounded bg-green-50 border border-green-200 text-green-700 text-sm">
                {{ session('status') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-4 p-3 rounded bg-red-50 border border-red-200 text-red-700 text-sm">
                {{ $errors->first('email') }}
            </div>
        @endif

        <p class="text-gray-600 text-sm mb-4">Enter your email address and we will send you a link to reset your password.</p>

        <form method="POST" action="{{ route('tenant.password.email') }}">
            @csrf

            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="email">Email</label>
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

            <div class="flex flex-col gap-3">
                <button
                    class="text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full"
                    style="background-color: var(--brand-primary);"
                    type="submit"
                >
                    Send Password Reset Link
                </button>
                <a href="{{ route('tenant.auth.login') }}" class="text-center text-sm hover:underline" style="color: var(--brand-primary);">Back to login</a>
            </div>
        </form>
    </div>
</div>
@endsection
