@props(['resources'])

@php
    $actions = ['view', 'create', 'update', 'delete'];
    $permissions = old('permissions', []);
@endphp

<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 border border-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-200">
                    Resource
                </th>
                @foreach($actions as $action)
                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-200">
                    {{ ucfirst($action) }}
                </th>
                @endforeach
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @foreach($resources as $resourceKey => $resourceName)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 text-sm font-medium text-gray-900 border-r border-gray-200">
                    {{ $resourceName }}
                </td>
                @foreach($actions as $action)
                <td class="px-4 py-3 text-center border-r border-gray-200">
                    @php
                        $permissionString = "{$resourceKey}.{$action}";
                        $isChecked = in_array($permissionString, $permissions);
                    @endphp
                    <input type="checkbox" 
                           name="permissions[]" 
                           value="{{ $permissionString }}"
                           id="perm_{{ $resourceKey }}_{{ $action }}"
                           {{ $isChecked ? 'checked' : '' }}
                           class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                </td>
                @endforeach
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

@error('permissions')
    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
@enderror

