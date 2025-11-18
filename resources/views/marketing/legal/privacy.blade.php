@extends('layouts.marketing')

@section('title', 'Privacy Policy - LegitBooks')

@section('content')
<section class="bg-white py-16">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-4xl font-bold text-gray-900 mb-8">Privacy Policy</h1>
        <div class="prose prose-lg max-w-none">
            <p class="text-gray-600 mb-4">Last updated: {{ date('F j, Y') }}</p>
            
            <h2 class="text-2xl font-bold text-gray-900 mt-8 mb-4">1. Information We Collect</h2>
            <p class="text-gray-600 mb-4">
                We collect information that you provide directly to us, including:
            </p>
            <ul class="list-disc pl-6 text-gray-600 mb-4">
                <li>Account information (name, email address, company name)</li>
                <li>Financial data you enter into LegitBooks (invoices, payments, expenses)</li>
                <li>Payment information for subscription billing</li>
                <li>Communication data when you contact our support team</li>
            </ul>
            
            <h2 class="text-2xl font-bold text-gray-900 mt-8 mb-4">2. How We Use Your Information</h2>
            <p class="text-gray-600 mb-4">
                We use the information we collect to:
            </p>
            <ul class="list-disc pl-6 text-gray-600 mb-4">
                <li>Provide, maintain, and improve our services</li>
                <li>Process transactions and send related information</li>
                <li>Send technical notices, updates, and support messages</li>
                <li>Respond to your comments and questions</li>
                <li>Monitor and analyze trends and usage</li>
            </ul>
            
            <h2 class="text-2xl font-bold text-gray-900 mt-8 mb-4">3. Data Security</h2>
            <p class="text-gray-600 mb-4">
                We implement appropriate technical and organizational security measures to protect your personal information. 
                This includes encryption of data in transit and at rest, regular security audits, and access controls.
            </p>
            
            <h2 class="text-2xl font-bold text-gray-900 mt-8 mb-4">4. Data Sharing</h2>
            <p class="text-gray-600 mb-4">
                We do not sell, trade, or rent your personal information to third parties. We may share your information only:
            </p>
            <ul class="list-disc pl-6 text-gray-600 mb-4">
                <li>With service providers who assist us in operating our platform (under strict confidentiality agreements)</li>
                <li>When required by law or to protect our rights</li>
                <li>In connection with a business transfer (merger, acquisition, etc.)</li>
            </ul>
            
            <h2 class="text-2xl font-bold text-gray-900 mt-8 mb-4">5. Your Rights</h2>
            <p class="text-gray-600 mb-4">
                You have the right to:
            </p>
            <ul class="list-disc pl-6 text-gray-600 mb-4">
                <li>Access your personal data</li>
                <li>Correct inaccurate data</li>
                <li>Request deletion of your data</li>
                <li>Export your data in a portable format</li>
                <li>Opt-out of marketing communications</li>
            </ul>
            
            <h2 class="text-2xl font-bold text-gray-900 mt-8 mb-4">6. Data Retention</h2>
            <p class="text-gray-600 mb-4">
                We retain your data for as long as your account is active or as needed to provide services. If you cancel 
                your account, we will retain your data for 30 days before permanent deletion, unless you request earlier deletion.
            </p>
            
            <h2 class="text-2xl font-bold text-gray-900 mt-8 mb-4">7. Cookies and Tracking</h2>
            <p class="text-gray-600 mb-4">
                We use cookies and similar tracking technologies to track activity on our service and hold certain information. 
                You can instruct your browser to refuse all cookies or to indicate when a cookie is being sent.
            </p>
            
            <h2 class="text-2xl font-bold text-gray-900 mt-8 mb-4">8. Changes to This Policy</h2>
            <p class="text-gray-600 mb-4">
                We may update our Privacy Policy from time to time. We will notify you of any changes by posting the new 
                Privacy Policy on this page and updating the "Last updated" date.
            </p>
            
            <h2 class="text-2xl font-bold text-gray-900 mt-8 mb-4">9. Contact Us</h2>
            <p class="text-gray-600 mb-4">
                If you have any questions about this Privacy Policy, please contact us at 
                <a href="{{ route('marketing.contact') }}" class="text-indigo-600 hover:text-indigo-900">support@legitbooks.com</a>.
            </p>
        </div>
    </div>
</section>
@endsection

