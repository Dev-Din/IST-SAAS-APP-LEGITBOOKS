@extends('layouts.tenant')

@section('title', 'Sign Up - LegitBooks')

@section('content')
<div class="max-w-md mx-auto mt-12 sm:mt-16 md:mt-20 px-4 sm:px-6 w-full">
    <div class="bg-white shadow-md rounded-lg px-6 sm:px-8 pt-6 pb-8 mb-4 w-full">
        <h2 class="text-2xl font-bold text-center mb-6" style="color: var(--brand-primary);">
            Start Your Free Trial
        </h2>
        
        <p class="text-sm text-gray-600 text-center mb-6">
            Create your account and start managing your business finances today.
        </p>

        <form method="POST" action="{{ route('tenant.auth.register.submit') }}">
            @csrf

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="company_name">
                    Company Name *
                </label>
                <input
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('company_name') border-red-500 @enderror"
                    id="company_name"
                    type="text"
                    name="company_name"
                    value="{{ old('company_name') }}"
                    required
                    autofocus
                    placeholder="Your Company Name"
                >
                @error('company_name')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="name">
                    Your Name *
                </label>
                <input
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('name') border-red-500 @enderror"
                    id="name"
                    type="text"
                    name="name"
                    value="{{ old('name') }}"
                    required
                    placeholder="Your Full Name"
                >
                @error('name')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                    Email Address *
                </label>
                <input
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('email') border-red-500 @enderror"
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    placeholder="your@email.com"
                >
                @error('email')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                    Password *
                </label>
                <input
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline @error('password') border-red-500 @enderror"
                    id="password"
                    type="password"
                    name="password"
                    required
                    placeholder="Minimum 8 characters"
                >
                @error('password')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="password_confirmation">
                    Confirm Password *
                </label>
                <input
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline"
                    id="password_confirmation"
                    type="password"
                    name="password_confirmation"
                    required
                    placeholder="Confirm your password"
                >
            </div>

            <div class="flex items-center justify-between">
                <button
                    class="text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full"
                    style="background-color: var(--brand-primary);"
                    type="submit"
                >
                    Create Account
                </button>
            </div>

            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    Already have an account? 
                    <a href="{{ route('tenant.auth.login') }}" class="font-bold" style="color: var(--brand-primary);">
                        Sign in
                    </a>
                </p>
            </div>
        </form>
    </div>
</div>
@endsection

