<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePlatformSettingsRequest;
use App\Services\PlatformSettings;
use Illuminate\Support\Facades\Auth;

class PlatformSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless(Auth::guard('admin')->check() && Auth::guard('admin')->user()->hasRole('superadmin'), 403);
            return $next($request);
        })->only(['index', 'update']);
    }

    public function index(PlatformSettings $settings)
    {
        $data = [
            'branding_mode' => $settings->get('branding_mode', config('legitbooks.branding_mode')),
            'mailgun_domain' => $settings->get('mailgun_domain', config('services.mailgun.domain')),
            'mailgun_secret' => $settings->get('mailgun_secret', config('services.mailgun.secret')),
            'mpesa_consumer_key' => $settings->get('mpesa_consumer_key', config('legitbooks.mpesa.consumer_key', env('MPESA_CONSUMER_KEY'))),
            'mpesa_consumer_secret' => $settings->get('mpesa_consumer_secret', config('legitbooks.mpesa.consumer_secret', env('MPESA_CONSUMER_SECRET'))),
            'mpesa_shortcode' => $settings->get('mpesa_shortcode', config('legitbooks.mpesa.shortcode', env('MPESA_SHORTCODE'))),
            'mpesa_passkey' => $settings->get('mpesa_passkey', config('legitbooks.mpesa.passkey', env('MPESA_PASSKEY'))),
            'mpesa_environment' => $settings->get('mpesa_environment', config('legitbooks.mpesa.environment', env('MPESA_ENVIRONMENT', 'sandbox'))),
        ];

        return view('admin.settings.index', compact('data'));
    }

    public function update(UpdatePlatformSettingsRequest $request, PlatformSettings $settings)
    {
        $validated = $request->validated();

        foreach ($validated as $key => $value) {
            $settings->set($key, $value);
        }

        config(['legitbooks.branding_mode' => $validated['branding_mode']]);

        return redirect()->route('admin.settings.index')->with('success', 'Settings updated successfully.');
    }
}
