<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function __construct(
        protected TenantContext $tenantContext
    ) {}

    /**
     * Show the user's profile page
     */
    public function index()
    {
        $tenant = $this->tenantContext->getTenant();
        $user = Auth::user();

        return view('tenant.profile.index', [
            'tenant' => $tenant,
            'user' => $user,
        ]);
    }

    /**
     * Update user profile information
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            'phone_country_code' => 'nullable|string|max:5',
            'phone_number' => 'nullable|string|max:20',
        ]);

        try {
            $user->update([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'name' => $validated['first_name'].' '.$validated['last_name'],
                'email' => $validated['email'],
                'phone_country_code' => $validated['phone_country_code'],
                'phone_number' => $validated['phone_number'],
            ]);

            return back()->with('success', 'Profile updated successfully.');
        } catch (\Exception $e) {
            Log::error('Profile update failed: '.$e->getMessage(), [
                'exception' => $e,
                'user_id' => $user->id,
            ]);

            return back()->withErrors(['error' => 'An error occurred while updating your profile. Please try again.']);
        }
    }

    /**
     * Update user password
     */
    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'current_password' => 'required',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        // Verify current password
        if (! Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'The current password is incorrect.']);
        }

        try {
            $user->update([
                'password' => Hash::make($validated['password']),
            ]);

            return back()->with('success', 'Password updated successfully.');
        } catch (\Exception $e) {
            Log::error('Password update failed: '.$e->getMessage(), [
                'exception' => $e,
                'user_id' => $user->id,
            ]);

            return back()->withErrors(['error' => 'An error occurred while updating your password. Please try again.']);
        }
    }

    /**
     * Update tenant information (for tenant owners)
     */
    public function updateTenant(Request $request)
    {
        $tenant = $this->tenantContext->getTenant();
        $user = Auth::user();

        // Only allow tenant owners to update tenant information
        if (! $user->is_owner) {
            return back()->withErrors(['error' => 'Only tenant owners can update tenant information.']);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
        ]);

        try {
            $tenant->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
            ]);

            return back()->with('success', 'Tenant information updated successfully.');
        } catch (\Exception $e) {
            Log::error('Tenant update failed: '.$e->getMessage(), [
                'exception' => $e,
                'tenant_id' => $tenant->id,
            ]);

            return back()->withErrors(['error' => 'An error occurred while updating tenant information. Please try again.']);
        }
    }
}
