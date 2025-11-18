@extends('layouts.admin')

@section('title', 'Admin Users')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="py-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Admin Users</h1>
            <a href="{{ route('admin.admins.create') }}" class="bg-slate-900 hover:bg-slate-800 text-white font-bold py-2 px-4 rounded">
                Create Admin
            </a>
        </div>

        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($admins as $admin)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $admin->name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $admin->email }}</td>
                        <td class="px-6 py-4 whitespace-nowrap capitalize">{{ $admin->role }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $admin->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ $admin->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="{{ route('admin.admins.edit', $admin) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</a>
                            @if(auth('admin')->id() !== $admin->id)
                            <form action="{{ route('admin.admins.destroy', $admin) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Delete this admin?')">Delete</button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">No admin users found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $admins->links() }}
        </div>
    </div>
</div>
@endsection
