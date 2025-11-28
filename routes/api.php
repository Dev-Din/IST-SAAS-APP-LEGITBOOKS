<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// M-Pesa STK Push routes
Route::prefix('payments/mpesa')->group(function () {
    Route::post('/stk-push', [\App\Http\Controllers\Api\MpesaStkController::class, 'initiateSTKPush'])->name('api.mpesa.stk-push');
    // Primary callback endpoint (idempotent, Cloudflare-aware, fallback search)
    Route::post('/callback', [\App\Http\Controllers\Payments\MpesaController::class, 'callback'])->name('api.mpesa.callback');
});

