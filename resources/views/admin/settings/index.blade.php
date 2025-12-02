@extends('layouts.admin')

@section('title', 'Platform Settings')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="py-6">
        <h1 class="text-3xl font-bold text-gray-900 mb-6">Platform Settings</h1>

        <div class="bg-white shadow rounded-lg p-6">
            <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Branding Mode</label>
                    <select name="branding_mode" class="shadow border rounded w-full py-2 px-3">
                        @foreach(['A' => 'Mode A - Tenant Name', 'B' => 'Mode B - LegitBooks Branding', 'C' => 'Mode C - White Label'] as $key => $label)
                            <option value="{{ $key }}" @selected(old('branding_mode', $data['branding_mode']) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <h3 class="text-lg font-semibold mb-2">M-Pesa Sandbox</h3>
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Consumer Key</label>
                            <input type="text" name="mpesa_consumer_key" value="{{ old('mpesa_consumer_key', $data['mpesa_consumer_key']) }}" class="shadow border rounded w-full py-2 px-3">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Consumer Secret</label>
                            <input type="text" name="mpesa_consumer_secret" value="{{ old('mpesa_consumer_secret', $data['mpesa_consumer_secret']) }}" class="shadow border rounded w-full py-2 px-3">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Shortcode</label>
                            <input type="text" name="mpesa_shortcode" value="{{ old('mpesa_shortcode', $data['mpesa_shortcode']) }}" class="shadow border rounded w-full py-2 px-3">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Passkey</label>
                            <input type="text" name="mpesa_passkey" value="{{ old('mpesa_passkey', $data['mpesa_passkey']) }}" class="shadow border rounded w-full py-2 px-3">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Environment</label>
                            <select name="mpesa_environment" class="shadow border rounded w-full py-2 px-3">
                                <option value="sandbox" @selected(old('mpesa_environment', $data['mpesa_environment']) === 'sandbox')>Sandbox</option>
                                <option value="production" @selected(old('mpesa_environment', $data['mpesa_environment']) === 'production')>Production</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="bg-slate-900 hover:bg-slate-800 text-white font-bold py-2 px-4 rounded">
                        Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
