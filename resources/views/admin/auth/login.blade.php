@extends('layouts.admin')

@section('title', 'Admin Login')

@section('content')
<div class="bg-white shadow-md rounded-lg px-6 sm:px-8 pt-6 pb-8 w-full">
    <h2 class="text-2xl font-bold text-center mb-6">LegitBooks Admin</h2>
    
    <form method="POST" action="{{ route('admin.login') }}">
        @csrf

        @if($errors->any())
            <div class="mb-4 p-3 rounded bg-red-50 border border-red-200 text-red-700 text-sm">
                {{ $errors->first('email') }}
            </div>
        @endif

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                Email
            </label>
            <input
                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('email') border-red-500 @enderror"
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="email"
            >
        </div>

        <div class="mb-6">
            <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                Password
            </label>
            <input
                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline @error('email') border-red-500 @enderror"
                id="password"
                type="password"
                name="password"
                required
                autocomplete="current-password"
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
@endsection

