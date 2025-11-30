@extends('layouts.admin')

@section('title', 'Invite Admin')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <h1 class="text-3xl font-bold text-gray-900 mb-6">Invite Admin User</h1>

    <!-- Pending Invitations -->
    @if(isset($pendingInvitations) && $pendingInvitations->count() > 0)
    <div class="bg-white shadow rounded-lg p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Pending Invitations</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expires</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($pendingInvitations as $invitation)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $invitation->first_name }} {{ $invitation->last_name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $invitation->email }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $invitation->role_name ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $invitation->expires_at->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <form method="POST" action="{{ route('admin.admins.resend-invite', $invitation->id) }}" class="inline">
                                @csrf
                                <button type="submit" class="text-indigo-600 hover:text-indigo-900 hover:underline">
                                    Resend Invite
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Create Invitation Form -->
    <div class="bg-white shadow rounded-lg p-6">
        <form method="POST" action="{{ route('admin.admins.store') }}" id="invite-form">
            @csrf

            <!-- Basic Information -->
            <div class="mb-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Basic Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">
                            First Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="first_name" id="first_name" value="{{ old('first_name') }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                            required>
                        @error('first_name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">
                            Last Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="last_name" id="last_name" value="{{ old('last_name') }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                            required>
                        @error('last_name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-4">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                        Email <span class="text-red-500">*</span>
                    </label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                        required>
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-4">
                    <label for="role_name" class="block text-sm font-medium text-gray-700 mb-1">
                        Role Name (Optional)
                    </label>
                    <input type="text" name="role_name" id="role_name" value="{{ old('role_name') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="e.g., Support Admin, Finance Admin">
                    @error('role_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Permission Matrix -->
            <div class="mb-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Permissions</h2>
                @include('components.permissions.matrix', ['resources' => $resources ?? []])
            </div>

            <!-- Permission Preview -->
            <div class="mb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-2">Selected Permissions Preview</h3>
                <div id="permission-preview" class="bg-gray-50 border border-gray-200 rounded-md p-4 min-h-[60px]">
                    <p class="text-sm text-gray-500">Select permissions above to see preview</p>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex items-center justify-end space-x-4">
                <a href="{{ route('admin.admins.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    Send Invitation
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('invite-form');
    const permissionPreview = document.getElementById('permission-preview');
    
    function updatePreview() {
        const selected = [];
        const checkboxes = form.querySelectorAll('input[type="checkbox"][name^="permissions"]:checked');
        
        checkboxes.forEach(cb => {
            selected.push(cb.value);
        });
        
        if (selected.length === 0) {
            permissionPreview.innerHTML = '<p class="text-sm text-gray-500">Select permissions above to see preview</p>';
        } else {
            permissionPreview.innerHTML = '<div class="flex flex-wrap gap-2">' +
                selected.map(p => `<span class="px-2 py-1 bg-indigo-100 text-indigo-800 text-xs rounded">${p}</span>`).join('') +
                '</div>';
        }
    }
    
    form.addEventListener('change', function(e) {
        if (e.target.type === 'checkbox' && e.target.name.startsWith('permissions')) {
            updatePreview();
        }
    });
    
    updatePreview();
});
</script>
@endsection
