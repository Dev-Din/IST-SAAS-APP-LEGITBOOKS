<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class EnsureUserHasAnyPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permissions  Comma-separated list of permissions
     */
    public function handle(Request $request, Closure $next, string $permissions): Response
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

        // Parse comma-separated permissions
        $permissionList = array_map('trim', explode(',', $permissions));

        // Check if user has any of the required permissions
        if (!$user->hasAnyPermission($permissionList)) {
            abort(403, 'You do not have permission to access this resource.');
        }

        return $next($request);
    }
}
