<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdminUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check() && auth('admin')->user()->hasRole('owner');
    }

    public function rules(): array
    {
        $adminId = $this->route('admin')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:admins,email,' . $adminId],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', 'in:owner,subadmin'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
