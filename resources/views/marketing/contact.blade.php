@extends('layouts.marketing')

@section('title', 'Contact Us - LegitBooks')

@section('content')
<!-- Hero -->
<section class="bg-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">Get in touch</h1>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">Have questions? We'd love to hear from you. Send us a message and we'll respond as soon as possible.</p>
        </div>
    </div>
</section>

<!-- Contact Form -->
<section class="py-16 bg-gray-50">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <form method="POST" action="{{ route('marketing.contact.submit') }}">
                @csrf
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Name *</label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" required class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                
                <div class="mb-6">
                    <label for="company" class="block text-sm font-medium text-gray-700 mb-2">Company</label>
                    <input type="text" name="company" id="company" value="{{ old('company') }}" class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    @error('company')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <div class="mb-6">
                    <label for="message" class="block text-sm font-medium text-gray-700 mb-2">Message *</label>
                    <textarea name="message" id="message" rows="6" required class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">{{ old('message') }}</textarea>
                    @error('message')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" class="px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white" style="background-color: var(--brand-primary);">
                        Send Message
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>

<!-- Additional Info -->
<section class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center">
            <div>
                <svg class="h-12 w-12 text-indigo-500 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Email Support</h3>
                <p class="text-gray-600">support@legitbooks.com</p>
            </div>
            
            <div>
                <svg class="h-12 w-12 text-indigo-500 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Response Time</h3>
                <p class="text-gray-600">We typically respond within 24 hours</p>
            </div>
            
            <div>
                <svg class="h-12 w-12 text-indigo-500 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                </svg>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Business Hours</h3>
                <p class="text-gray-600">Monday - Friday, 9 AM - 5 PM EAT</p>
            </div>
        </div>
    </div>
</section>
@endsection

