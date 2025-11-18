<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TenantAuthController extends Controller
{
    public function showLoginForm()
    {
        // Allow login page without tenant context
        // Tenant will be resolved from user after login
        return view('tenant.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Add is_active to credentials
        $credentials['is_active'] = true;

        if (Auth::attempt($credentials, $request->filled('remember'))) {
            $user = Auth::user();
            
            // Get tenant from user and store in session
            if ($user->tenant_id) {
                $request->session()->put('tenant_id', $user->tenant_id);
            }
            
            $request->session()->regenerate();
            return redirect()->intended(route('tenant.dashboard'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->forget('tenant_id');
        $request->session()->regenerateToken();
        return redirect()->route('tenant.auth.login');
    }
}
