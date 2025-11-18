<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Marketing\HomeController;
use App\Http\Controllers\Marketing\FeaturesController;
use App\Http\Controllers\Marketing\PricingController;
use App\Http\Controllers\Marketing\SolutionsController;
use App\Http\Controllers\Marketing\AboutController;
use App\Http\Controllers\Marketing\ContactController;
use App\Http\Controllers\Marketing\FaqController;
use App\Http\Controllers\Marketing\LegalController;

// Marketing routes (public, no auth required)
Route::get('/', [HomeController::class, 'index'])->name('marketing.home');
Route::get('/features', [FeaturesController::class, 'index'])->name('marketing.features');
Route::get('/pricing', [PricingController::class, 'index'])->name('marketing.pricing');
Route::get('/solutions', [SolutionsController::class, 'index'])->name('marketing.solutions');
Route::get('/about', [AboutController::class, 'index'])->name('marketing.about');
Route::get('/contact', [ContactController::class, 'showForm'])->name('marketing.contact');
Route::post('/contact', [ContactController::class, 'submitForm'])->name('marketing.contact.submit');
Route::get('/faq', [FaqController::class, 'index'])->name('marketing.faq');
Route::get('/legal/terms', [LegalController::class, 'terms'])->name('marketing.legal.terms');
Route::get('/legal/privacy', [LegalController::class, 'privacy'])->name('marketing.legal.privacy');

// Admin routes
Route::middleware('web')
    ->prefix('admin')
    ->name('admin.')
    ->group(base_path('routes/admin.php'));

// Tenant routes
Route::middleware('web')
    ->prefix('app')
    ->name('tenant.')
    ->group(base_path('routes/tenant.php'));
