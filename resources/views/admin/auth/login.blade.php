@extends('layouts.admin')

@section('title', 'Admin Login')

@section('content')
<div class="max-w-md mx-auto mt-20">
    <div class="bg-white shadow-md rounded-lg px-8 pt-6 pb-8 mb-4">
        <h2 class="text-2xl font-bold text-center mb-6">LegitBooks Admin</h2>
        
        <form method="POST" action="{{ route('admin.login') }}">
            @csrf

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                    Email
                </label>
                <input
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                >
            </div>

            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                    Password
                </label>
                <input
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline"
                    id="password"
                    type="password"
                    name="password"
                    required
                >
            </div>

            <div class="flex items-center justify-between">
                <button
                    class="bg-slate-900 hover:bg-slate-800 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full"
                    type="submit"
                >
                    Sign In
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

