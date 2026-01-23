<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdminInvitationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth('admin')->check() && auth('admin')->user()->hasRole('owner');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('admin_invitations')->where(function ($query) {
                    return $query->where('status', 'pending')
                        ->where('expires_at', '>', now());
                }),
            ],
            'role_name' => ['nullable', 'string', 'max:255'],
            'permissions' => ['required', 'array'],
            'permissions.*' => ['string', 'regex:/^[a-z_]+\.[a-z_]+$/'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'An active invitation already exists for this email address.',
            'permissions.required' => 'At least one permission must be assigned.',
            'permissions.*.regex' => 'Permission format must be resource.action (e.g., tenants.view).',
        ];
    }
}
