@extends('layouts.marketing')

@section('title', 'About Us - LegitBooks')

@section('content')
<!-- Hero -->
<section class="bg-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">About LegitBooks</h1>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">We're on a mission to make accounting simple and accessible for small businesses across Kenya.</p>
        </div>
    </div>
</section>

<!-- Story -->
<section class="py-16 bg-gray-50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-gray-900 mb-6">Our Story</h2>
        <div class="prose prose-lg text-gray-600">
            <p>
                LegitBooks was born from a simple observation: small businesses in Kenya were struggling with accounting. 
                Spreadsheets were error-prone, traditional accounting software was too complex, and hiring accountants was expensive.
            </p>
            <p>
                We set out to create a solution that combines the simplicity small businesses need with the power and accuracy 
                that professional accounting requires. LegitBooks is built specifically for the Kenyan market, with M-Pesa 
                integration, local tax considerations, and a user-friendly interface.
            </p>
            <p>
                Today, LegitBooks helps hundreds of businesses manage their finances with confidence, from freelancers to 
                growing SMEs. We're committed to making professional accounting accessible to everyone.
            </p>
        </div>
    </div>
</section>

<!-- Mission & Vision -->
<section class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
            <div>
                <h2 class="text-3xl font-bold text-gray-900 mb-6">Our Mission</h2>
                <p class="text-lg text-gray-600">
                    To empower small businesses in Kenya with simple, accurate, and affordable cloud accounting solutions 
                    that help them grow and succeed.
                </p>
            </div>
            <div>
                <h2 class="text-3xl font-bold text-gray-900 mb-6">Our Vision</h2>
                <p class="text-lg text-gray-600">
                    To become the leading accounting platform for small businesses in Kenya, making professional financial 
                    management accessible to everyone, regardless of size or technical expertise.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- What Makes Us Different -->
<section class="py-16 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-center text-gray-900 mb-12">What makes us different</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="text-center">
                <div class="bg-indigo-100 rounded-lg p-6 mb-4 inline-block">
                    <svg class="h-12 w-12 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Built for Kenya</h3>
                <p class="text-gray-600">Designed specifically for the Kenyan market with M-Pesa integration and local business needs in mind.</p>
            </div>
            
            <div class="text-center">
                <div class="bg-green-100 rounded-lg p-6 mb-4 inline-block">
                    <svg class="h-12 w-12 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Simple & Fast</h3>
                <p class="text-gray-600">No accounting degree required. Our intuitive interface makes financial management accessible to everyone.</p>
            </div>
            
            <div class="text-center">
                <div class="bg-blue-100 rounded-lg p-6 mb-4 inline-block">
                    <svg class="h-12 w-12 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Secure & Reliable</h3>
                <p class="text-gray-600">Your data is encrypted, backed up, and protected. We take security and reliability seriously.</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="py-16 bg-gray-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl font-bold text-white mb-4">Join us on this journey</h2>
        <p class="text-xl text-gray-300 mb-8">Start your free trial and experience the difference.</p>
        <a href="{{ route('tenant.auth.register') }}" class="inline-flex items-center px-8 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700">
            Get started
        </a>
    </div>
</section>
@endsection

