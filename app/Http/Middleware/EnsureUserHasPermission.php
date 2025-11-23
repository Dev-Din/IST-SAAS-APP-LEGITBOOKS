<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class EnsureUserHasPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permission
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('tenant.auth.login');
        }

        // Check if user is active
        if (!$user->is_active) {
            Auth::logout();
            return redirect()->route('tenant.auth.login')
                ->with('error', 'Your account has been deactivated.');
        }

        // For manage_users permission, allow if:
        // 1. User has the permission, OR
        // 2. User is the first/only user in the tenant (initial setup)
        if ($permission === 'manage_users') {
            if ($user->hasPermission($permission)) {
                return $next($request);
            }
            
            // Check if user is the first user in tenant
            $tenantUserCount = \App\Models\User::where('tenant_id', $user->tenant_id)->count();
            if ($tenantUserCount <= 1) {
                // Grant permission automatically for first user
                // Safely handle permissions - ensure it's always an array
                $permissions = is_array($user->permissions) ? $user->permissions : [];
                if (!in_array('manage_users', $permissions)) {
                    $permissions[] = 'manage_users';
                    // Refresh user to ensure we have latest data
                    $user->refresh();
                    $user->permissions = $permissions;
                    $user->save();
                }
                return $next($request);
            }
        }

        // Check if user has the required permission
        if (!$user->hasPermission($permission)) {
            abort(403, 'You do not have permission to access this resource.');
        }

        return $next($request);
    }
}
