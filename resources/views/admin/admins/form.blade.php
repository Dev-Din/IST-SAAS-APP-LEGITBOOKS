@php($admin = $admin ?? new \App\Models\Admin())
@csrf
<div class="grid grid-cols-1 gap-4">
    <div>
        <label class="block text-sm font-medium text-gray-700">Name</label>
        <input type="text" name="name" value="{{ old('name', $admin->name ?? '') }}" class="shadow border rounded w-full py-2 px-3" required>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700">Email</label>
        <input type="email" name="email" value="{{ old('email', $admin->email ?? '') }}" class="shadow border rounded w-full py-2 px-3" required>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700">Password @isset($admin)<span class="text-sm text-gray-500">(leave blank to keep current)</span>@endisset</label>
        <input type="password" name="password" class="shadow border rounded w-full py-2 px-3" @empty($admin) required @endempty>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700">Role</label>
        <select name="role" class="shadow border rounded w-full py-2 px-3" required>
            @foreach(['superadmin' => 'Superadmin', 'subadmin' => 'Sub-admin'] as $value => $label)
                <option value="{{ $value }}" @selected(old('role', $admin->role ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="flex items-center">
        <input type="checkbox" name="is_active" value="1" class="mr-2" @checked(old('is_active', $admin->is_active ?? true))>
        <span class="text-sm text-gray-700">Active</span>
    </div>
</div>
<div class="flex justify-end space-x-3 mt-6">
    <a href="{{ route('admin.admins.index') }}" class="text-gray-600 hover:text-gray-900">Cancel</a>
    <button type="submit" class="bg-slate-900 hover:bg-slate-800 text-white font-bold py-2 px-4 rounded">Save</button>
</div>
