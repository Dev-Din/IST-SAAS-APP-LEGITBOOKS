<?php

use App\Http\Controllers\Tenant\BillController;
use App\Http\Controllers\Tenant\ChartOfAccountController;
use App\Http\Controllers\Tenant\ContactController;
use App\Http\Controllers\Tenant\DashboardController;
use App\Http\Controllers\Tenant\InvoiceController;
use App\Http\Controllers\Tenant\MpesaController;
use App\Http\Controllers\Tenant\PaymentController;
use App\Http\Controllers\Tenant\ProductController;
use App\Http\Controllers\Tenant\ReportsController;
use App\Http\Controllers\Tenant\TenantAuthController;
use Illuminate\Support\Facades\Route;

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
    // Dashboard
    Route::middleware(['permission:view_dashboard'])->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    });

    // Invoices
    Route::middleware(['anypermission:manage_invoices,view_invoices'])->group(function () {
        // Export route must come before resource route to avoid conflicts
        Route::get('invoices/export', [InvoiceController::class, 'export'])->name('invoices.export');
        Route::resource('invoices', InvoiceController::class);
        Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])->name('invoices.pdf');
        Route::post('invoices/{invoice}/send', [InvoiceController::class, 'sendEmail'])->name('invoices.send');
        Route::get('invoices/{invoice}/receipt', [InvoiceController::class, 'receipt'])->name('invoices.receipt');
    });

    // Bills
    Route::middleware(['anypermission:manage_bills,view_bills'])->group(function () {
        Route::resource('bills', BillController::class);
        Route::post('bills/{bill}/mark-received', [BillController::class, 'markReceived'])->name('bills.mark-received');
    });

    // Payments
    Route::middleware(['anypermission:manage_payments,view_payments'])->group(function () {
        // Payment Receipts - Must be defined BEFORE resource route to avoid route conflicts
        Route::get('payments/receipts', [\App\Http\Controllers\Tenant\PaymentReceiptController::class, 'index'])->name('payments.receipts');
        Route::get('payments/receipts/{payment}', [\App\Http\Controllers\Tenant\PaymentReceiptController::class, 'show'])->name('payments.receipts.show');
        Route::post('payments/receipts/{payment}/validate', [\App\Http\Controllers\Tenant\PaymentReceiptController::class, 'validate'])->name('payments.receipts.validate');
        Route::post('payments/receipts/fetch', [\App\Http\Controllers\Tenant\PaymentReceiptController::class, 'fetchByReceipt'])->name('payments.receipts.fetch');
        Route::post('payments/receipts/validate-pending', [\App\Http\Controllers\Tenant\PaymentReceiptController::class, 'validatePending'])->name('payments.receipts.validate-pending');

        // Payment JSON API - Must be defined BEFORE resource route to avoid route conflicts
        Route::get('payments/json/fetch', [\App\Http\Controllers\Tenant\PaymentJsonController::class, 'fetch'])->name('payments.json.fetch');
        Route::post('payments/json/store', [\App\Http\Controllers\Tenant\PaymentJsonController::class, 'store'])->name('payments.json.store');
        Route::get('payments/json/files', [\App\Http\Controllers\Tenant\PaymentJsonController::class, 'listFiles'])->name('payments.json.files');
        Route::get('payments/json/download/{filename}', [\App\Http\Controllers\Tenant\PaymentJsonController::class, 'download'])->name('payments.json.download');

        // Payment Resource Routes - Must be defined AFTER specific routes
        Route::resource('payments', PaymentController::class);
    });

    // Contacts
    Route::middleware(['anypermission:manage_contacts,view_contacts'])->group(function () {
        Route::resource('contacts', ContactController::class);
    });

    // Products
    Route::middleware(['anypermission:manage_products,view_products'])->group(function () {
        Route::resource('products', ProductController::class);
    });

    // Chart of Accounts
    Route::middleware(['anypermission:manage_chart_of_accounts,view_chart_of_accounts'])->group(function () {
        Route::resource('chart-of-accounts', ChartOfAccountController::class);
    });

    // Reports
    Route::middleware(['permission:view_reports'])->group(function () {
        Route::get('reports', [ReportsController::class, 'index'])->name('reports.index');
    });

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
    Route::middleware(['permission:manage_billing'])->group(function () {
        Route::get('billing', [\App\Http\Controllers\Tenant\BillingController::class, 'index'])->name('billing.index');
        Route::get('billing/page', [\App\Http\Controllers\Tenant\BillingController::class, 'page'])->name('billing.page');
        Route::post('billing/upgrade', [\App\Http\Controllers\Tenant\BillingController::class, 'upgrade'])->name('billing.upgrade');
        Route::get('billing/payment-status', [\App\Http\Controllers\Tenant\BillingController::class, 'checkPaymentStatus'])->name('billing.payment-status');

        // M-Pesa STK Push endpoints
        Route::post('billing/mpesa/initiate', [\App\Http\Controllers\Tenant\BillingController::class, 'initiateMpesaPayment'])->name('billing.mpesa.initiate');
        Route::get('billing/mpesa/status/{checkoutRequestID}', [\App\Http\Controllers\Tenant\BillingController::class, 'checkMpesaStatus'])->name('billing.mpesa.status');

        Route::post('billing/plan', [\App\Http\Controllers\Tenant\BillingController::class, 'updatePlan'])->name('billing.update-plan');
        Route::post('billing/payment-methods', [\App\Http\Controllers\Tenant\BillingController::class, 'storePaymentMethod'])->name('billing.payment-methods.store');
        Route::post('billing/payment-methods/{paymentMethod}/set-default', [\App\Http\Controllers\Tenant\BillingController::class, 'setDefaultPaymentMethod'])->name('billing.payment-methods.set-default');
        Route::delete('billing/payment-methods/{paymentMethod}', [\App\Http\Controllers\Tenant\BillingController::class, 'destroyPaymentMethod'])->name('billing.payment-methods.destroy');
    });

    // Profile Management
    Route::get('profile', [\App\Http\Controllers\Tenant\ProfileController::class, 'index'])->name('profile.index');
    Route::put('profile', [\App\Http\Controllers\Tenant\ProfileController::class, 'updateProfile'])->name('profile.update');
    Route::put('profile/password', [\App\Http\Controllers\Tenant\ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::put('profile/tenant', [\App\Http\Controllers\Tenant\ProfileController::class, 'updateTenant'])->name('profile.tenant');

    // Checkout & M-Pesa Payment Flow
    Route::post('checkout/{plan}/pay-mpesa', [\App\Http\Controllers\Tenant\CheckoutController::class, 'payWithMpesa'])->name('checkout.pay-mpesa');
    Route::get('checkout/{plan}/mpesa-status/{token}', [\App\Http\Controllers\Tenant\CheckoutController::class, 'mpesaStatus'])->name('checkout.mpesa-status');
});

Route::post('/mpesa/callback', [MpesaController::class, 'callback'])->name('mpesa.callback');
