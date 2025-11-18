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

Route::middleware([\App\Http\Middleware\ResolveTenant::class, \App\Http\Middleware\EnsureTenantActive::class, 'auth:web'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('invoices', InvoiceController::class);
    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])->name('invoices.pdf');
    Route::post('invoices/{invoice}/send', [InvoiceController::class, 'sendEmail'])->name('invoices.send');

    Route::resource('payments', PaymentController::class);
    Route::resource('contacts', ContactController::class);
    Route::resource('products', ProductController::class);
    Route::resource('chart-of-accounts', ChartOfAccountController::class);

    // Billing & Subscriptions
    Route::get('billing', [\App\Http\Controllers\Tenant\BillingController::class, 'index'])->name('billing.index');
    Route::post('billing/plan', [\App\Http\Controllers\Tenant\BillingController::class, 'updatePlan'])->name('billing.update-plan');
    Route::post('billing/payment-methods', [\App\Http\Controllers\Tenant\BillingController::class, 'storePaymentMethod'])->name('billing.payment-methods.store');
    Route::post('billing/payment-methods/{paymentMethod}/set-default', [\App\Http\Controllers\Tenant\BillingController::class, 'setDefaultPaymentMethod'])->name('billing.payment-methods.set-default');
    Route::delete('billing/payment-methods/{paymentMethod}', [\App\Http\Controllers\Tenant\BillingController::class, 'destroyPaymentMethod'])->name('billing.payment-methods.destroy');
});

Route::post('/mpesa/callback', [MpesaController::class, 'callback'])->name('mpesa.callback');

