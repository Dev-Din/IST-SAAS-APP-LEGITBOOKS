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

// Registration routes - no tenant context required
Route::get('/auth/register', [\App\Http\Controllers\Tenant\TenantRegistrationController::class, 'showRegistrationForm'])->name('auth.register');
Route::post('/auth/register', [\App\Http\Controllers\Tenant\TenantRegistrationController::class, 'register'])->name('auth.register.submit');
Route::get('/auth/billing', [\App\Http\Controllers\Tenant\TenantRegistrationController::class, 'showBillingForm'])->name('auth.billing');
Route::post('/auth/billing', [\App\Http\Controllers\Tenant\TenantRegistrationController::class, 'processBilling'])->name('auth.billing.submit');

// Login routes - no tenant context required initially
Route::get('/auth/login', [TenantAuthController::class, 'showLoginForm'])->name('auth.login');
Route::post('/auth/login', [TenantAuthController::class, 'login']);
Route::post('/auth/logout', [TenantAuthController::class, 'logout'])->name('auth.logout');

Route::middleware([\App\Http\Middleware\ResolveTenant::class, \App\Http\Middleware\EnsureTenantActive::class, 'auth:web', 'user.active'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('invoices', InvoiceController::class);
    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])->name('invoices.pdf');
    Route::post('invoices/{invoice}/send', [InvoiceController::class, 'sendEmail'])->name('invoices.send');
    Route::get('invoices/{invoice}/receipt', [InvoiceController::class, 'receipt'])->name('invoices.receipt');

    Route::resource('payments', PaymentController::class);
    Route::resource('contacts', ContactController::class);
    Route::resource('products', ProductController::class);
    Route::resource('chart-of-accounts', ChartOfAccountController::class);

    // User Management
    Route::middleware(['permission:manage_users'])->group(function () {
        Route::get('users', [\App\Http\Controllers\Tenant\TenantUserController::class, 'index'])->name('users.index');
        Route::get('users/invite', [\App\Http\Controllers\Tenant\TenantUserController::class, 'create'])->name('users.create');
        Route::post('users/invite', [\App\Http\Controllers\Tenant\TenantUserController::class, 'store'])->name('users.store');
        Route::get('users/{user}/edit', [\App\Http\Controllers\Tenant\TenantUserController::class, 'edit'])->name('users.edit');
        Route::put('users/{user}', [\App\Http\Controllers\Tenant\TenantUserController::class, 'update'])->name('users.update');
        Route::patch('users/{user}/activate', [\App\Http\Controllers\Tenant\TenantUserController::class, 'activate'])->name('users.activate');
        Route::patch('users/{user}/deactivate', [\App\Http\Controllers\Tenant\TenantUserController::class, 'deactivate'])->name('users.deactivate');
        Route::delete('users/{user}', [\App\Http\Controllers\Tenant\TenantUserController::class, 'destroy'])->name('users.destroy');
        Route::post('invitations/{invitation}/resend', [\App\Http\Controllers\Tenant\TenantUserController::class, 'resendInvitation'])->name('invitations.resend');
        Route::post('invitations/{invitation}/cancel', [\App\Http\Controllers\Tenant\TenantUserController::class, 'cancelInvitation'])->name('invitations.cancel');
        Route::delete('invitations/{invitation}', [\App\Http\Controllers\Tenant\TenantUserController::class, 'destroyInvitation'])->name('invitations.destroy');
    });

    // Billing & Subscriptions
    Route::get('billing', [\App\Http\Controllers\Tenant\BillingController::class, 'index'])->name('billing.index');
    Route::get('billing/page', [\App\Http\Controllers\Tenant\BillingController::class, 'page'])->name('billing.page');
    Route::post('billing/upgrade', [\App\Http\Controllers\Tenant\BillingController::class, 'upgrade'])->name('billing.upgrade');
    Route::post('billing/plan', [\App\Http\Controllers\Tenant\BillingController::class, 'updatePlan'])->name('billing.update-plan');
    Route::post('billing/payment-methods', [\App\Http\Controllers\Tenant\BillingController::class, 'storePaymentMethod'])->name('billing.payment-methods.store');
    Route::post('billing/payment-methods/{paymentMethod}/set-default', [\App\Http\Controllers\Tenant\BillingController::class, 'setDefaultPaymentMethod'])->name('billing.payment-methods.set-default');
    Route::delete('billing/payment-methods/{paymentMethod}', [\App\Http\Controllers\Tenant\BillingController::class, 'destroyPaymentMethod'])->name('billing.payment-methods.destroy');
});

Route::post('/mpesa/callback', [MpesaController::class, 'callback'])->name('mpesa.callback');

