<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    public function __construct(
        protected TenantContext $tenantContext
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = null;

        // Try to get tenant from session first
        $tenantId = $request->session()->get('tenant_id');
        if ($tenantId) {
            $tenant = Tenant::find($tenantId);
        }

        // If not in session, try to get from authenticated user
        if (! $tenant && Auth::check()) {
            $user = Auth::user();
            if ($user && $user->tenant_id) {
                $tenant = Tenant::find($user->tenant_id);
                // Store in session for future requests
                if ($tenant) {
                    $request->session()->put('tenant_id', $tenant->id);
                }
            }
        }

        // If still no tenant, try to get from route parameter (for backward compatibility)
        if (! $tenant) {
            $tenantHash = $request->route('tenant_hash');
            if ($tenantHash) {
                $tenant = Tenant::where('tenant_hash', $tenantHash)->first();
                if ($tenant) {
                    $request->session()->put('tenant_id', $tenant->id);
                }
            }
        }

        if (! $tenant) {
            // Redirect to login if not authenticated, or show error
            if (! Auth::check()) {
                return redirect()->route('tenant.auth.login');
            }
            abort(404, 'Tenant not found');
        }

        $this->tenantContext->setTenant($tenant);

        return $next($request);
    }
}
