<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdminUserRequest;
use App\Http\Requests\Admin\UpdateAdminUserRequest;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class AdminUserController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless(Auth::guard('admin')->check() && Auth::guard('admin')->user()->hasRole('superadmin'), 403);
            return $next($request);
        });
    }

    public function index()
    {
        $admins = Admin::orderByDesc('created_at')->paginate(15);
        $roles = Role::where('guard_name', 'admin')->pluck('name');

        return view('admin.admins.index', compact('admins', 'roles'));
    }

    public function create()
    {
        $roles = Role::where('guard_name', 'admin')->pluck('name', 'name');
        return view('admin.admins.create', compact('roles'));
    }

    public function store(StoreAdminUserRequest $request)
    {
        $data = $request->validated();
        $admin = Admin::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'is_active' => $request->boolean('is_active'),
        ]);

        $admin->assignRole($data['role']);

        return redirect()->route('admin.admins.index')->with('success', 'Admin user created successfully.');
    }

    public function edit(Admin $admin)
    {
        $roles = Role::where('guard_name', 'admin')->pluck('name', 'name');
        return view('admin.admins.edit', compact('admin', 'roles'));
    }

    public function update(UpdateAdminUserRequest $request, Admin $admin)
    {
        $data = $request->validated();

        $admin->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'is_active' => $request->boolean('is_active'),
        ]);

        if (!empty($data['password'])) {
            $admin->update(['password' => Hash::make($data['password'])]);
        }

        $admin->syncRoles([$data['role']]);

        return redirect()->route('admin.admins.index')->with('success', 'Admin user updated successfully.');
    }

    public function destroy(Admin $admin)
    {
        abort_if(Auth::guard('admin')->id() === $admin->id, 403, 'You cannot delete yourself.');
        $admin->delete();

        return redirect()->route('admin.admins.index')->with('success', 'Admin user deleted successfully.');
    }
}
