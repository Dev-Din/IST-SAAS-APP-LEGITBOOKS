@extends('layouts.marketing')

@section('title', 'Terms of Service - LegitBooks')

@section('content')
<section class="bg-white py-16">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-4xl font-bold text-gray-900 mb-8">Terms of Service</h1>
        <div class="prose prose-lg max-w-none">
            <p class="text-gray-600 mb-4">Last updated: {{ date('F j, Y') }}</p>
            
            <h2 class="text-2xl font-bold text-gray-900 mt-8 mb-4">1. Acceptance of Terms</h2>
            <p class="text-gray-600 mb-4">
                By accessing and using LegitBooks, you accept and agree to be bound by the terms and provision of this agreement. 
                If you do not agree to abide by the above, please do not use this service.
            </p>
            
            <h2 class="text-2xl font-bold text-gray-900 mt-8 mb-4">2. Use License</h2>
            <p class="text-gray-600 mb-4">
                Permission is granted to temporarily use LegitBooks for personal or commercial business accounting purposes. 
                This is the grant of a license, not a transfer of title, and under this license you may not:
            </p>
            <ul class="list-disc pl-6 text-gray-600 mb-4">
                <li>Modify or copy the materials</li>
                <li>Use the materials for any commercial purpose or for any public display</li>
                <li>Attempt to reverse engineer any software contained in LegitBooks</li>
                <li>Remove any copyright or other proprietary notations from the materials</li>
            </ul>
            
            <h2 class="text-2xl font-bold text-gray-900 mt-8 mb-4">3. Account Registration</h2>
            <p class="text-gray-600 mb-4">
                You are responsible for maintaining the confidentiality of your account and password. You agree to accept 
                responsibility for all activities that occur under your account or password.
            </p>
            
            <h2 class="text-2xl font-bold text-gray-900 mt-8 mb-4">4. Subscription and Billing</h2>
            <p class="text-gray-600 mb-4">
                Subscriptions are billed monthly in advance. You may cancel your subscription at any time, and cancellation 
                will take effect at the end of your current billing period. No refunds are provided for partial billing periods.
            </p>
            
            <h2 class="text-2xl font-bold text-gray-900 mt-8 mb-4">5. Data and Privacy</h2>
            <p class="text-gray-600 mb-4">
                You retain all rights to your data. We will not access, use, or share your data except as necessary to provide 
                the service or as required by law. Please review our Privacy Policy for more information.
            </p>
            
            <h2 class="text-2xl font-bold text-gray-900 mt-8 mb-4">6. Limitation of Liability</h2>
            <p class="text-gray-600 mb-4">
                LegitBooks shall not be liable for any indirect, incidental, special, consequential, or punitive damages, 
                including without limitation, loss of profits, data, use, goodwill, or other intangible losses.
            </p>
            
            <h2 class="text-2xl font-bold text-gray-900 mt-8 mb-4">7. Changes to Terms</h2>
            <p class="text-gray-600 mb-4">
                We reserve the right to modify these terms at any time. We will notify users of any material changes via email 
                or through the service. Continued use of the service after changes constitutes acceptance of the new terms.
            </p>
            
            <h2 class="text-2xl font-bold text-gray-900 mt-8 mb-4">8. Contact Information</h2>
            <p class="text-gray-600 mb-4">
                If you have any questions about these Terms of Service, please contact us at 
                <a href="{{ route('marketing.contact') }}" class="text-indigo-600 hover:text-indigo-900">support@legitbooks.com</a>.
            </p>
        </div>
    </div>
</section>
@endsection

