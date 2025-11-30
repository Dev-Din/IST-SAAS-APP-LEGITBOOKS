<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invitation Expired - LegitBooks</title>
    @vite(['resources/css/admin.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center px-4 sm:px-6">
        <div class="max-w-md w-full">
            <div class="bg-white shadow-md rounded-lg px-6 sm:px-8 pt-6 pb-8 text-center">
                <div class="mb-4">
                    <svg class="mx-auto h-12 w-12 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 mb-4">Invitation Expired</h2>
                <p class="text-gray-600 mb-6">
                    This invitation link has expired or is no longer valid. Please contact your administrator to request a new invitation.
                </p>
                <a href="{{ route('admin.login') }}" class="inline-block px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                    Go to Login
                </a>
            </div>
        </div>
    </div>
</body>
</html>

