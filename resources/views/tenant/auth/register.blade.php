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
                <label class="block text-gray-700 text-sm font-bold mb-2" for="phone">
                    Phone Number
                </label>
                <div class="flex gap-2">
                    <div class="w-24 flex-shrink-0">
                        <select
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('phone_country_code') border-red-500 @enderror"
                            id="phone_country_code"
                            name="phone_country_code"
                        >
                            <option value="">--</option>
                            <option value="KE" {{ old('phone_country_code') === 'KE' ? 'selected' : '' }}>KE (+254)</option>
                            <option value="TZ" {{ old('phone_country_code') === 'TZ' ? 'selected' : '' }}>TZ (+255)</option>
                            <option value="UG" {{ old('phone_country_code') === 'UG' ? 'selected' : '' }}>UG (+256)</option>
                        </select>
                    </div>
                    <div class="flex-1">
                        <input
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('phone_number') border-red-500 @enderror"
                            id="phone_number"
                            type="tel"
                            name="phone_number"
                            value="{{ old('phone_number') }}"
                            placeholder="Phone number"
                        >
                    </div>
                </div>
                @error('phone_country_code')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
                @error('phone_number')
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

            <div class="mb-6">
                <div class="flex items-start">
                    <input
                        class="mt-1 mr-2 h-4 w-4 text-gray-700 focus:ring-gray-500 border-gray-300 rounded @error('accept_terms') border-red-500 @enderror"
                        id="accept_terms"
                        type="checkbox"
                        name="accept_terms"
                        value="1"
                        {{ old('accept_terms') ? 'checked' : '' }}
                        required
                    >
                    <label class="text-sm text-gray-700" for="accept_terms">
                        I accept the 
                        <a href="{{ route('marketing.home') }}" target="_blank" class="font-bold hover:underline" style="color: var(--brand-primary);">
                            Terms and Conditions
                        </a>
                        *
                    </label>
                </div>
                @error('accept_terms')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
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

