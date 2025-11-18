@extends('layouts.marketing')

@section('title', 'FAQ - LegitBooks')

@section('content')
<!-- Hero -->
<section class="bg-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">Frequently Asked Questions</h1>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">Find answers to common questions about LegitBooks.</p>
        </div>
    </div>
</section>

<!-- FAQ Accordion -->
<section class="py-16 bg-gray-50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="space-y-4">
            <!-- FAQ Item 1 -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <button class="w-full px-6 py-4 text-left flex justify-between items-center focus:outline-none" onclick="toggleFaq(1)">
                    <span class="text-lg font-semibold text-gray-900">What is included in the free trial?</span>
                    <svg id="icon-1" class="h-5 w-5 text-gray-500 transform transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div id="content-1" class="hidden px-6 pb-4">
                    <p class="text-gray-600">The free trial includes full access to all features of your chosen plan for 14 days. No credit card is required to start your trial. You can create invoices, track payments, generate reports, and explore all features during this period.</p>
                </div>
            </div>

            <!-- FAQ Item 2 -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <button class="w-full px-6 py-4 text-left flex justify-between items-center focus:outline-none" onclick="toggleFaq(2)">
                    <span class="text-lg font-semibold text-gray-900">How does billing work?</span>
                    <svg id="icon-2" class="h-5 w-5 text-gray-500 transform transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div id="content-2" class="hidden px-6 pb-4">
                    <p class="text-gray-600">Billing is monthly and automatically renews. You can upgrade, downgrade, or cancel your subscription at any time from your account settings. Changes take effect at the start of your next billing cycle. We accept M-Pesa and bank transfers for payments.</p>
                </div>
            </div>

            <!-- FAQ Item 3 -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <button class="w-full px-6 py-4 text-left flex justify-between items-center focus:outline-none" onclick="toggleFaq(3)">
                    <span class="text-lg font-semibold text-gray-900">Is my data secure?</span>
                    <svg id="icon-3" class="h-5 w-5 text-gray-500 transform transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div id="content-3" class="hidden px-6 pb-4">
                    <p class="text-gray-600">Yes, absolutely. We use industry-standard encryption to protect your data both in transit and at rest. All data is stored securely in our cloud infrastructure with regular backups. We never share your data with third parties, and you can export your data at any time.</p>
                </div>
            </div>

            <!-- FAQ Item 4 -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <button class="w-full px-6 py-4 text-left flex justify-between items-center focus:outline-none" onclick="toggleFaq(4)">
                    <span class="text-lg font-semibold text-gray-900">Can multiple users access the same account?</span>
                    <svg id="icon-4" class="h-5 w-5 text-gray-500 transform transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div id="content-4" class="hidden px-6 pb-4">
                    <p class="text-gray-600">Yes, depending on your plan. The Starter plan includes up to 3 users, Business plan includes up to 10 users, and Enterprise includes unlimited users. Each user can have different permission levels (admin, accountant, viewer) to control what they can access and modify.</p>
                </div>
            </div>

            <!-- FAQ Item 5 -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <button class="w-full px-6 py-4 text-left flex justify-between items-center focus:outline-none" onclick="toggleFaq(5)">
                    <span class="text-lg font-semibold text-gray-900">How do I cancel my subscription?</span>
                    <svg id="icon-5" class="h-5 w-5 text-gray-500 transform transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div id="content-5" class="hidden px-6 pb-4">
                    <p class="text-gray-600">You can cancel your subscription at any time from your account settings. Your subscription will remain active until the end of your current billing period, and you'll continue to have access to all features during that time. After cancellation, you can still access your data in read-only mode for 30 days.</p>
                </div>
            </div>

            <!-- FAQ Item 6 -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <button class="w-full px-6 py-4 text-left flex justify-between items-center focus:outline-none" onclick="toggleFaq(6)">
                    <span class="text-lg font-semibold text-gray-900">Does LegitBooks integrate with M-Pesa?</span>
                    <svg id="icon-6" class="h-5 w-5 text-gray-500 transform transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div id="content-6" class="hidden px-6 pb-4">
                    <p class="text-gray-600">Yes! LegitBooks has built-in M-Pesa integration (available on Starter, Business, and Enterprise plans). You can accept payments via M-Pesa, and transactions are automatically reconciled in your account. This makes it easy to track payments and maintain accurate records.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="py-16 bg-gray-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl font-bold text-white mb-4">Still have questions?</h2>
        <p class="text-xl text-gray-300 mb-8">Contact our support team and we'll be happy to help.</p>
        <a href="{{ route('marketing.contact') }}" class="inline-flex items-center px-8 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700">
            Contact Support
        </a>
    </div>
</section>

<script>
function toggleFaq(num) {
    const content = document.getElementById('content-' + num);
    const icon = document.getElementById('icon-' + num);
    const isHidden = content.classList.contains('hidden');
    
    // Close all other FAQs
    for (let i = 1; i <= 6; i++) {
        if (i !== num) {
            document.getElementById('content-' + i).classList.add('hidden');
            document.getElementById('icon-' + i).classList.remove('rotate-180');
        }
    }
    
    // Toggle current FAQ
    if (isHidden) {
        content.classList.remove('hidden');
        icon.classList.add('rotate-180');
    } else {
        content.classList.add('hidden');
        icon.classList.remove('rotate-180');
    }
}
</script>
@endsection

