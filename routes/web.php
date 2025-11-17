<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Admin routes
Route::middleware('web')
    ->prefix('admin')
    ->name('admin.')
    ->group(base_path('routes/admin.php'));

// Tenant routes
Route::middleware('web')
    ->prefix('app/{tenant_hash}')
    ->name('tenant.')
    ->group(base_path('routes/tenant.php'));
