<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Tenant\InvoiceController;
use App\Http\Controllers\Tenant\PaymentController;
use App\Http\Controllers\Tenant\ContactController;
use App\Http\Controllers\Tenant\ProductController;
use App\Http\Controllers\Tenant\ChartOfAccountController;
use App\Http\Controllers\Tenant\DashboardController;
use App\Http\Controllers\Tenant\TenantAuthController;
use App\Http\Controllers\Tenant\MpesaController;

Route::middleware([\App\Http\Middleware\ResolveTenant::class])->group(function () {
    Route::get('/auth/login', [TenantAuthController::class, 'showLoginForm'])->name('auth.login');
    Route::post('/auth/login', [TenantAuthController::class, 'login']);
    Route::post('/auth/logout', [TenantAuthController::class, 'logout'])->name('auth.logout');
});

Route::middleware([\App\Http\Middleware\ResolveTenant::class, \App\Http\Middleware\EnsureTenantActive::class, 'auth:web'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('invoices', InvoiceController::class);
    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])->name('invoices.pdf');
    Route::post('invoices/{invoice}/send', [InvoiceController::class, 'sendEmail'])->name('invoices.send');

    Route::resource('payments', PaymentController::class);
    Route::resource('contacts', ContactController::class);
    Route::resource('products', ProductController::class);
    Route::resource('chart-of-accounts', ChartOfAccountController::class);
});

Route::post('/mpesa/callback', [MpesaController::class, 'callback'])->name('mpesa.callback');

