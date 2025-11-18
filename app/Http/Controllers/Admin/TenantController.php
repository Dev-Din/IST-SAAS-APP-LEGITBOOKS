<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\TenantProvisioningService;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function index()
    {
        $tenants = Tenant::latest()->paginate(15);
        return view('admin.tenants.index', compact('tenants'));
    }

    public function create()
    {
        return view('admin.tenants.create');
    }

    public function store(Request $request, TenantProvisioningService $provisioningService)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:tenants,email',
        ]);

        $tenant = Tenant::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'tenant_hash' => Tenant::generateTenantHash(),
            'status' => 'active',
            'settings' => [
                'branding_override' => null,
            ],
        ]);

        $provisioningService->provision($tenant, [
            'create_admin' => $request->boolean('create_admin'),
            'admin_email' => $request->input('admin_email', $validated['email']),
            'admin_password' => $request->input('admin_password'),
            'seed_demo_data' => $request->boolean('seed_demo_data'),
        ]);

        return redirect()->route('admin.tenants.index')
            ->with('success', "Tenant '{$tenant->name}' created successfully!");
    }

    public function show(Tenant $tenant)
    {
        return view('admin.tenants.show', compact('tenant'));
    }

    public function suspend(Tenant $tenant)
    {
        $tenant->update(['status' => 'suspended']);
        return back()->with('success', 'Tenant suspended successfully');
    }

    public function updateBranding(Request $request, Tenant $tenant)
    {
        $settings = $tenant->settings ?? [];
        $settings['branding_override'] = $request->input('branding_override');
        $tenant->update(['settings' => $settings]);
        return back()->with('success', 'Branding updated successfully');
    }
}
