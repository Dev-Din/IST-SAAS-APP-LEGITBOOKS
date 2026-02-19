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

    // Test endpoint to verify tunnel and callback URL are reachable (for debugging)
    Route::post('/callback-test', function (\Illuminate\Http\Request $request) {
        $body = $request->all();
        $rawBody = $request->getContent();

        \Illuminate\Support\Facades\Log::info('M-Pesa callback TEST endpoint hit', [
            'ip' => $request->ip(),
            'headers' => $request->headers->all(),
            'body_preview' => substr($rawBody, 0, 1000),
            'parsed_body' => $body,
            'timestamp' => now()->toIso8601String(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Callback test endpoint received your request',
            'received_at' => now()->toIso8601String(),
            'body_size' => strlen($rawBody),
        ], 200);
    })->name('api.mpesa.callback-test');
});
