@extends('layouts.tenant')

@section('title', 'Edit User')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="py-6">
        <h1 class="text-3xl font-bold text-gray-900 mb-6">Edit User</h1>

        <div class="bg-white shadow sm:rounded-lg">
            <form action="{{ route('tenant.users.update', $user) }}" method="POST" class="p-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label for="first_name" class="block text-sm font-medium text-gray-700">First Name</label>
                        <input type="text" name="first_name" id="first_name" value="{{ old('first_name', $user->first_name) }}" required
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('first_name') border-red-300 @enderror">
                        @error('first_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="last_name" class="block text-sm font-medium text-gray-700">Last Name</label>
                        <input type="text" name="last_name" id="last_name" value="{{ old('last_name', $user->last_name) }}" required
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('last_name') border-red-300 @enderror">
                        @error('last_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-6">
                    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" name="email" id="email" value="{{ $user->email }}" disabled
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-50 sm:text-sm">
                    <p class="mt-1 text-sm text-gray-500">Email cannot be changed</p>
                </div>

                @if($user->is_owner)
                <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-md">
                    <p class="text-sm text-yellow-800">
                        <strong>Account Owner:</strong> This user is the account owner and has full access to all features. Role and permissions cannot be modified.
                    </p>
                </div>
                <div class="mt-6">
                    <label for="role_name" class="block text-sm font-medium text-gray-700">Role Name</label>
                    <input type="text" name="role_name" id="role_name" value="{{ old('role_name', $user->role_name) }}" disabled
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-50 sm:text-sm">
                    <p class="mt-1 text-sm text-gray-500">Role cannot be changed for account owner</p>
                </div>
                <div class="mt-6">
                    <label class="block text-sm font-medium text-gray-700 mb-3">Permissions</label>
                    <div class="p-4 bg-gray-50 border border-gray-200 rounded-md">
                        <p class="text-sm text-gray-600">Account owner has all permissions enabled.</p>
                    </div>
                </div>
                @else
                <div class="mt-6">
                    <label for="role_name" class="block text-sm font-medium text-gray-700">Role Name</label>
                    <input type="text" name="role_name" id="role_name" value="{{ old('role_name', $user->role_name) }}" required placeholder="e.g., Accountant, Clerk, Manager"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('role_name') border-red-300 @enderror">
                    @error('role_name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-6">
                    <label class="block text-sm font-medium text-gray-700 mb-3">Permissions</label>
                    <div class="space-y-4">
                        @foreach($permissionGroups as $groupKey => $group)
                        <div class="border border-gray-200 rounded-lg p-4">
                            <h3 class="text-sm font-semibold text-gray-900 mb-3">{{ $group['label'] }}</h3>
                            <div class="space-y-2">
                                @foreach($group['permissions'] as $permissionKey)
                                    @if(isset($permissions[$permissionKey]))
                                    <label class="flex items-center">
                                        <input type="checkbox" name="permissions[]" value="{{ $permissionKey }}" 
                                            {{ in_array($permissionKey, old('permissions', $user->permissions ?? [])) ? 'checked' : '' }}
                                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        <span class="ml-2 text-sm text-gray-700">
                                            {{ $permissions[$permissionKey]['label'] }}
                                        </span>
                                    </label>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @error('permissions')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                @endif

                <div class="mt-6 flex justify-end space-x-3">
                    <a href="{{ route('tenant.users.index') }}" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Cancel
                    </a>
                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" style="background-color: var(--brand-primary);">
                        Update User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

