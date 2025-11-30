<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\PlatformSettingsController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminInvitationController;
use App\Http\Controllers\Admin\AdminProfileController;

Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AdminAuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

Route::middleware(['auth:admin'])->group(function () {
    Route::get('/', function () {
        return view('admin.dashboard');
    })->name('dashboard');

    Route::resource('tenants', TenantController::class);
    Route::patch('tenants/{tenant}/suspend', [TenantController::class, 'suspend'])->name('tenants.suspend');
    Route::patch('tenants/{tenant}/branding', [TenantController::class, 'updateBranding'])->name('tenants.branding');

    Route::get('settings', [PlatformSettingsController::class, 'index'])->name('settings.index');
    Route::post('settings', [PlatformSettingsController::class, 'update'])->name('settings.update');
    Route::resource('admins', AdminUserController::class)->except(['show']);
    Route::post('admins/{invitation}/resend-invite', [AdminUserController::class, 'resendInvite'])->name('admins.resend-invite');
    
    // Profile Management
    Route::get('profile', [AdminProfileController::class, 'index'])->name('profile.index');
    Route::put('profile', [AdminProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [AdminProfileController::class, 'updatePassword'])->name('profile.password');
});

// Public invite acceptance routes (no auth required)
Route::get('/invite/accept/{token}', [AdminInvitationController::class, 'showAccept'])->name('admin.invite.accept');
Route::post('/invite/accept/{token}', [AdminInvitationController::class, 'accept']);

