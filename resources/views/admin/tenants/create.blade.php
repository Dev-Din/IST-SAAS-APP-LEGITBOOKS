@extends('layouts.admin')

@section('title', 'Create Tenant')

@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="py-6">
        <h1 class="text-3xl font-bold text-gray-900 mb-6">Create New Tenant</h1>

        <div class="bg-white shadow rounded-lg p-6">
            <form method="POST" action="{{ route('admin.tenants.store') }}">
                @csrf

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="name">
                        Tenant Name *
                    </label>
                    <input
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                        id="name"
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        required
                    >
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                        Email *
                    </label>
                    <input
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                    >
                </div>

                <div class="mb-4">
                    <label class="flex items-center">
                        <input type="checkbox" name="create_admin" value="1" class="mr-2" checked>
                        <span class="text-sm text-gray-700">Create tenant admin user</span>
                    </label>
                </div>

                <div class="mb-4" id="admin-email-field" style="display: none;">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="admin_email">
                        Admin Email
                    </label>
                    <input
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                        id="admin_email"
                        type="email"
                        name="admin_email"
                        value="{{ old('admin_email') }}"
                    >
                </div>

                <div class="mb-4" id="admin-password-field" style="display: none;">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="admin_password">
                        Admin Password (leave blank for auto-generated)
                    </label>
                    <input
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                        id="admin_password"
                        type="password"
                        name="admin_password"
                    >
                </div>

                <div class="mb-6">
                    <label class="flex items-center">
                        <input type="checkbox" name="seed_demo_data" value="1" class="mr-2">
                        <span class="text-sm text-gray-700">Seed demo data</span>
                    </label>
                </div>

                <div class="flex items-center justify-between">
                    <a href="{{ route('admin.tenants.index') }}" class="text-gray-600 hover:text-gray-900">
                        Cancel
                    </a>
                    <button
                        class="bg-slate-900 hover:bg-slate-800 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                        type="submit"
                    >
                        Create Tenant
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const createAdminCheckbox = document.querySelector('input[name="create_admin"]');
const adminFields = ['admin-email-field', 'admin-password-field'];
function toggleAdminFields() {
    adminFields.forEach(id => {
        document.getElementById(id).style.display = createAdminCheckbox.checked ? 'block' : 'none';
    });
}
createAdminCheckbox.addEventListener('change', toggleAdminFields);
toggleAdminFields();
</script>
@endsection

