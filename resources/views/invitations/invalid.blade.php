<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invalid Invitation - LegitBooks</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            background-color: #f8fafc;
        }
    </style>
</head>
<body>
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Invalid Invitation
                </h2>
            </div>
            <div class="bg-white shadow-md rounded-lg px-8 pt-6 pb-8">
                @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    {{ session('error') }}
                </div>
                @endif

                <p class="text-gray-700 text-center mb-4">
                    This invitation link is invalid, expired, or has already been used.
                </p>
                <p class="text-gray-600 text-sm text-center mb-6">
                    Please contact your administrator to request a new invitation.
                </p>
                <div class="text-center">
                    <a href="{{ route('marketing.home') }}" class="text-indigo-600 hover:text-indigo-900">
                        Return to Home
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

