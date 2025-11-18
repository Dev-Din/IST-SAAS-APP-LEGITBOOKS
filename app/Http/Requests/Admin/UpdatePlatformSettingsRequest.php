<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlatformSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check() && auth('admin')->user()->hasRole('superadmin');
    }

    public function rules(): array
    {
        return [
            'branding_mode' => ['required', 'in:A,B,C'],
            'mailgun_domain' => ['nullable', 'string', 'max:255'],
            'mailgun_secret' => ['nullable', 'string', 'max:255'],
            'mpesa_consumer_key' => ['nullable', 'string', 'max:255'],
            'mpesa_consumer_secret' => ['nullable', 'string', 'max:255'],
            'mpesa_shortcode' => ['nullable', 'string', 'max:255'],
            'mpesa_passkey' => ['nullable', 'string', 'max:255'],
            'mpesa_environment' => ['required', 'in:sandbox,production'],
        ];
    }
}
