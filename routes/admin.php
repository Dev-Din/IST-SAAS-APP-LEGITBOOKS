<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\PlatformSettingsController;

Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AdminAuthController::class, 'login']);
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
});

