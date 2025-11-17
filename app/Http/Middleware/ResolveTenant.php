<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Tenant;
use App\Services\TenantContext;

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
        $tenantHash = $request->route('tenant_hash');
        
        if (!$tenantHash) {
            abort(404, 'Tenant hash not found in route');
        }

        $tenant = Tenant::where('tenant_hash', $tenantHash)->first();

        if (!$tenant) {
            abort(404, 'Tenant not found');
        }

        $this->tenantContext->setTenant($tenant);

        return $next($request);
    }
}
