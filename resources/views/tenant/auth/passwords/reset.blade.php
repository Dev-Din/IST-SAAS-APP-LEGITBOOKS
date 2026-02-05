@extends('layouts.tenant')

@section('title', 'Reset Password')

@section('content')
<div class="max-w-md mx-auto mt-12 sm:mt-16 md:mt-20 px-4 sm:px-6 w-full">
    <div class="bg-white shadow-md rounded-lg px-6 sm:px-8 pt-6 pb-8 mb-4 w-full">
        @php
            $tenant = app(\App\Services\TenantContext::class)->getTenant();
            $brandMode = $tenant ? $tenant->getBrandingMode() : env('BRANDING_MODE', 'A');
        @endphp

        @if($brandMode === 'B')
            <h2 class="text-2xl font-bold text-center mb-6">Reset Password</h2>
        @else
            <h2 class="text-2xl font-bold text-center mb-6" style="color: var(--brand-primary);">Reset Password</h2>
        @endif

        @if($errors->any())
            <div class="mb-4 p-3 rounded bg-red-50 border border-red-200 text-red-700 text-sm">
                {{ $errors->first('email') ?: $errors->first('password') }}
            </div>
        @endif

        <form method="POST" action="{{ route('tenant.password.update') }}">
            @csrf

            <input type="hidden" name="token" value="{{ $token }}">

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="email">Email</label>
                <input
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('email') border-red-500 @enderror"
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email', $email) }}"
                    required
                    autofocus
                    autocomplete="email"
                >
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="password">New Password</label>
                <input
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('password') border-red-500 @enderror"
                    id="password"
                    type="password"
                    name="password"
                    required
                    autocomplete="new-password"
                >
            </div>

            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="password_confirmation">Confirm Password</label>
                <input
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                    id="password_confirmation"
                    type="password"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                >
            </div>

            <div class="flex flex-col gap-3">
                <button
                    class="text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full"
                    style="background-color: var(--brand-primary);"
                    type="submit"
                >
                    Reset Password
                </button>
                <a href="{{ route('tenant.auth.login') }}" class="text-center text-sm hover:underline" style="color: var(--brand-primary);">Back to login</a>
            </div>
        </form>
    </div>
</div>
@endsection
