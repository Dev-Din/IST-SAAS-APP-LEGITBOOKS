@extends('layouts.tenant')

@section('title', 'User Management')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="py-6">
        @if(session('status'))
        <div class="mb-4 rounded-md bg-green-50 p-3 text-sm text-green-800">
            {{ session('status') }}
        </div>
        @endif

        @if($errors->any())
        <div class="mb-4 rounded-md bg-red-50 p-3 text-sm text-red-800">
            {{ $errors->first() }}
        </div>
        @endif

        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-900">User Management</h1>
            @perm('manage_users')
            <a href="{{ route('tenant.users.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white" style="background-color: var(--brand-primary);">
                Invite User
            </a>
            @endperm
        </div>

        <!-- Users Section -->
        <div class="mb-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Users</h2>
            <div class="bg-white shadow overflow-hidden sm:rounded-md">
                <ul class="divide-y divide-gray-200">
                    @forelse($users as $user)
                    <li>
                        <div class="px-4 py-4 sm:px-6 hover:bg-gray-50">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center">
                                        <p class="text-sm font-medium text-gray-900">
                                            {{ $user->full_name ?: $user->name }}
                                        </p>
                                        <span class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            @if($user->is_active) bg-green-100 text-green-800
                                            @else bg-red-100 text-red-800
                                            @endif">
                                            {{ $user->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </div>
                                    <div class="mt-1">
                                        <p class="text-sm text-gray-500">{{ $user->email }}</p>
                                        @if($user->role_name)
                                        <p class="text-sm text-gray-500">Role: {{ $user->role_name }}</p>
                                        @endif
                                        @if($user->is_owner)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800 mt-1">
                                            Account Owner
                                        </span>
                                        @endif
                                    </div>
                                </div>
                                <div class="relative">
                                    @perm('manage_users')
                                    @if(!$user->is_owner && $user->id !== auth()->id())
                                    <!-- Kebab Menu Button -->
                                    <button type="button" 
                                            class="kebab-menu-btn text-gray-400 hover:text-gray-600 focus:outline-none focus:text-gray-600 p-1"
                                            onclick="toggleKebabMenu('user-{{ $user->id }}')"
                                            aria-label="User actions menu">
                                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"></path>
                                        </svg>
                                    </button>
                                    
                                    <!-- Kebab Menu Dropdown - Positioned outside card -->
                                    <div id="kebab-menu-user-{{ $user->id }}" 
                                         class="kebab-menu hidden fixed rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50"
                                         style="display: none;">
                                        <div class="py-1 min-w-[120px]" role="menu">
                                            <a href="{{ route('tenant.users.edit', $user) }}" 
                                               class="block px-4 py-2 text-sm text-indigo-600 hover:bg-gray-100" 
                                               role="menuitem">
                                                Edit
                                            </a>
                                            <hr class="my-1 border-gray-200">
                                            @if($user->is_active)
                                            <form action="{{ route('tenant.users.deactivate', $user) }}" method="POST" class="block">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" 
                                                        class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100" 
                                                        role="menuitem">
                                                    Deactivate
                                                </button>
                                            </form>
                                            @else
                                            <form action="{{ route('tenant.users.activate', $user) }}" method="POST" class="block">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" 
                                                        class="w-full text-left px-4 py-2 text-sm text-green-600 hover:bg-gray-100" 
                                                        role="menuitem">
                                                    Activate
                                                </button>
                                            </form>
                                            @endif
                                            <hr class="my-1 border-gray-200">
                                            <form action="{{ route('tenant.users.destroy', $user) }}" method="POST" class="block">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                        class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100" 
                                                        role="menuitem"
                                                        onclick="return confirm('Are you sure you want to permanently delete this user account? This action cannot be undone.');">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    @elseif($user->is_owner)
                                    <span class="text-sm text-gray-400">Owner</span>
                                    @else
                                    <span class="text-sm text-gray-400">You</span>
                                    @endif
                                    @endperm
                                </div>
                            </div>
                        </div>
                    </li>
                    @empty
                    <li class="px-4 py-8 text-center text-gray-500">
                        No users found.
                        @perm('manage_users')
                        <a href="{{ route('tenant.users.create') }}" class="text-indigo-600 hover:text-indigo-900">Invite your first user</a>
                        @endperm
                    </li>
                    @endforelse
                </ul>
            </div>
        </div>

        <!-- Invitations Section -->
        <div>
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Pending Invitations</h2>
            <div class="bg-white shadow overflow-hidden sm:rounded-md">
                <ul class="divide-y divide-gray-200">
                    @forelse($invitations as $invitation)
                    <li>
                        <div class="px-4 py-4 sm:px-6 hover:bg-gray-50">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center">
                                        <p class="text-sm font-medium text-gray-900">
                                            {{ $invitation->full_name }}
                                        </p>
                                        <span class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            @if($invitation->status === 'pending' && !$invitation->isExpired()) bg-blue-100 text-blue-800
                                            @elseif($invitation->status === 'accepted') bg-green-100 text-green-800
                                            @elseif($invitation->status === 'expired' || $invitation->isExpired()) bg-red-100 text-red-800
                                            @else bg-gray-100 text-gray-800
                                            @endif">
                                            @if($invitation->status === 'pending' && $invitation->isExpired())
                                                Expired
                                            @else
                                                {{ ucfirst($invitation->status) }}
                                            @endif
                                        </span>
                                    </div>
                                    <div class="mt-1">
                                        <p class="text-sm text-gray-500">{{ $invitation->email }}</p>
                                        @if($invitation->role_name)
                                        <p class="text-sm text-gray-500">Role: {{ $invitation->role_name }}</p>
                                        @endif
                                        <p class="text-sm text-gray-500">Invited: {{ $invitation->created_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</p>
                                        @if($invitation->status === 'pending')
                                        <p class="text-sm text-gray-500">Expires: {{ $invitation->expires_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="relative">
                                    @perm('manage_users')
                                    @if($invitation->status === 'pending' && !$invitation->isExpired())
                                    <!-- Kebab Menu Button -->
                                    <button type="button" 
                                            class="kebab-menu-btn text-gray-400 hover:text-gray-600 focus:outline-none focus:text-gray-600 p-1"
                                            onclick="toggleKebabMenu('invitation-{{ $invitation->id }}')"
                                            aria-label="Invitation actions menu">
                                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"></path>
                                        </svg>
                                    </button>
                                    
                                    <!-- Kebab Menu Dropdown - Positioned outside card -->
                                    <div id="kebab-menu-invitation-{{ $invitation->id }}" 
                                         class="kebab-menu hidden fixed rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50"
                                         style="display: none;">
                                        <div class="py-1 min-w-[120px]" role="menu">
                                            <form action="{{ route('tenant.invitations.resend', $invitation) }}" method="POST" class="block">
                                                @csrf
                                                <button type="submit" 
                                                        class="w-full text-left px-4 py-2 text-sm text-indigo-600 hover:bg-gray-100" 
                                                        role="menuitem">
                                                    Resend
                                                </button>
                                            </form>
                                            <hr class="my-1 border-gray-200">
                                            <form action="{{ route('tenant.invitations.cancel', $invitation) }}" method="POST" class="block">
                                                @csrf
                                                <button type="submit" 
                                                        class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100" 
                                                        role="menuitem"
                                                        onclick="return confirm('Are you sure you want to cancel this invitation?');">
                                                    Cancel
                                                </button>
                                            </form>
                                            <hr class="my-1 border-gray-200">
                                            <form action="{{ route('tenant.invitations.destroy', $invitation) }}" method="POST" class="block">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                        class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100" 
                                                        role="menuitem"
                                                        onclick="return confirm('Are you sure you want to delete this invitation? This action cannot be undone.');">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    @elseif($invitation->isExpired())
                                    <span class="text-sm text-gray-500 mr-2">Expired</span>
                                    <!-- Kebab Menu Button for Expired -->
                                    <button type="button" 
                                            class="kebab-menu-btn text-gray-400 hover:text-gray-600 focus:outline-none focus:text-gray-600 p-1"
                                            onclick="toggleKebabMenu('invitation-{{ $invitation->id }}')"
                                            aria-label="Invitation actions menu">
                                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"></path>
                                        </svg>
                                    </button>
                                    
                                    <!-- Kebab Menu Dropdown for Expired - Positioned outside card -->
                                    <div id="kebab-menu-invitation-{{ $invitation->id }}" 
                                         class="kebab-menu hidden fixed rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50"
                                         style="display: none;">
                                        <div class="py-1 min-w-[120px]" role="menu">
                                            <form action="{{ route('tenant.invitations.destroy', $invitation) }}" method="POST" class="block">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                        class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100" 
                                                        role="menuitem"
                                                        onclick="return confirm('Are you sure you want to delete this invitation? This action cannot be undone.');">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    @endif
                                    @endperm
                                </div>
                            </div>
                        </div>
                    </li>
                    @empty
                    <li class="px-4 py-8 text-center text-gray-500">
                        No pending invitations.
                    </li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleKebabMenu(menuId) {
        const menu = document.getElementById('kebab-menu-' + menuId);
        if (!menu) return;
        
        const button = event.target.closest('.kebab-menu-btn');
        if (!button) return;
        
        const isHidden = menu.style.display === 'none' || menu.classList.contains('hidden');
        
        // Close all other menus
        document.querySelectorAll('.kebab-menu').forEach(m => {
            if (m.id !== 'kebab-menu-' + menuId) {
                m.style.display = 'none';
                m.classList.add('hidden');
            }
        });
        
        // Toggle current menu
        if (isHidden) {
            // Get button position
            const rect = button.getBoundingClientRect();
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;
            
            // Position menu above the button
            menu.style.position = 'fixed';
            menu.style.top = (rect.top + scrollTop - menu.offsetHeight - 8) + 'px';
            menu.style.right = (window.innerWidth - rect.right - scrollLeft) + 'px';
            menu.style.display = 'block';
            menu.classList.remove('hidden');
        } else {
            menu.style.display = 'none';
            menu.classList.add('hidden');
        }
    }
    
    // Close menu when clicking outside
    document.addEventListener('click', function(event) {
        if (!event.target.closest('.kebab-menu-btn') && !event.target.closest('.kebab-menu')) {
            document.querySelectorAll('.kebab-menu').forEach(menu => {
                menu.style.display = 'none';
                menu.classList.add('hidden');
            });
        }
    });
</script>
@endsection

