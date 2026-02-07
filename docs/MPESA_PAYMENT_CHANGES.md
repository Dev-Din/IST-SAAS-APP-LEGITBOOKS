# M-Pesa Payment System -- Changes & Fixes Reference

> **Date:** 07/02/2026
> **Project:** IST-SAAS-APP-LEGITBOOKS
> **Scope:** M-Pesa STK Push payment flow, invoice posting, callback handling, receipt storage

---

## Table of Contents

1. [Overview](#1-overview)
2. [Issues Fixed](#2-issues-fixed)
3. [Architecture: How M-Pesa Payments Work](#3-architecture-how-m-pesa-payments-work)
4. [File-by-File Changes](#4-file-by-file-changes)
5. [Database Migration](#5-database-migration)
6. [Cloudflare Tunnel Setup](#6-cloudflare-tunnel-setup)
7. [Known Limitations](#7-known-limitations)
8. [Troubleshooting Guide](#8-troubleshooting-guide)

---

## 1. Overview

Multiple issues were discovered and fixed in the M-Pesa payment pipeline. The core problems were:

- **Duplicate invoice numbers** across tenants due to a global unique constraint.
- **Payment confirmation page stuck** on "Confirming your payment..." indefinitely.
- **`mpesa_receipt` always NULL** in the `payments` table despite successful M-Pesa transactions.
- **Invoice payment page returning 404** ("This invoice has not been sent yet") after sending.
- **Missing Chart of Account records** causing silent transaction rollbacks.
- **Daraja API rate limiting** (429 Spike Arrest Violation) from aggressive polling.

---

## 2. Issues Fixed

### 2.1 Duplicate Invoice Number Across Tenants

**Problem:** The `invoices` table had a global `UNIQUE` constraint on `invoice_number`. Two different tenants could not both have `INV-001`.

**Root Cause:** `invoices.invoice_number` was globally unique instead of per-tenant.

**Fix:** Migration to change the unique constraint to a composite `(tenant_id, invoice_number)`.

**File:** `database/migrations/2026_01_23_121600_update_invoices_table_per_tenant_unique.php`

---

### 2.2 Payment Confirmation Page Stuck on "Confirming..."

**Problem:** After the end-client completed the M-Pesa payment on their phone, the success page kept showing "Confirming your payment..." and never progressed to the receipt.

**Root Cause (Primary):** The `syncInvoicePendingPaymentsWithDaraja()` method on the success page was calling `PaymentService::processPayment()` to allocate the payment. That method required `ChartOfAccount` code `1200` (Accounts Receivable), which did not exist for the tenant. The missing account caused an exception, which rolled back the entire DB transaction, leaving the payment stuck in `pending`.

**Root Cause (Secondary):** The success page was polling every 3 seconds with up to 40 reloads. Each reload triggered a Daraja STK Query API call. Daraja's sandbox allows only 5 requests per 60 seconds, so the page quickly hit `429 Spike Arrest Violation` errors, preventing the sync from ever succeeding.

**Fixes:**
- Auto-create `ChartOfAccount` 1200 (Accounts Receivable) and 2200 (Unapplied Credits) in `PaymentService::processPayment()` if missing.
- Increased success page `reloadDelayMs` from `3000` to `8000` (8 seconds between polls).
- Reduced `maxReloads` from `40` to `15` to stay within rate limits.

**Files:**
- `app/Services/PaymentService.php`
- `resources/views/invoice/payment/success.blade.php`

---

### 2.3 Invoice Payment Page 404 Error

**Problem:** After sending an invoice via email, clicking the payment link returned a `404` error: "This invoice has not been sent yet."

**Root Cause:** The `InvoiceSendService::sendInvoice()` method runs inside a `DB::transaction()`. It generates a `payment_token`, saves it to the invoice, then calls `InvoicePostingService::postInvoice()` to create journal entries. The posting service required `ChartOfAccount` codes `1200`, `4100`, and `2200` -- if any were missing, an exception was thrown, rolling back the **entire** transaction (including the `payment_token` save). However, the email with the payment link was dispatched **before** the transaction committed, so the user received a link to an invoice whose `payment_token` was rolled back to `NULL`.

**Fix:** Auto-create missing Chart of Account records inside `InvoicePostingService::postInvoice()`:
- `1200` -- Accounts Receivable (asset / current_asset)
- `4100` -- Sales Revenue (revenue / operating_revenue)
- `2200` -- Tax Payable (liability / current_liability)

**File:** `app/Services/InvoicePostingService.php`

---

### 2.4 `mpesa_receipt` Always NULL in Database

**Problem:** After a successful M-Pesa payment, the `payments` table showed `transaction_status = 'completed'` but `mpesa_receipt = NULL`. The M-Pesa receipt code (e.g. `UB7A264PZB`) was never stored.

**Root Cause (Two-Part):**

**Part 1 -- Daraja STK Query API Limitation:**
The success page sync path uses the Daraja STK Push Query API (`stkpushquery/v1/query`) to check payment status. This API returns:

```json
{
    "ResponseCode": "0",
    "MerchantRequestID": "...",
    "CheckoutRequestID": "...",
    "ResultCode": "0",
    "ResultDesc": "The service request is processed successfully."
}
```

**The STK Query API does NOT return `MpesaReceiptNumber`.** The receipt number is only available in the **callback payload** that Daraja POSTs to the `CallBackURL`. Therefore, the sync path can mark a payment as `completed` but can never populate `mpesa_receipt`.

**Part 2 -- Cloudflare Tunnel Unreliable:**
The Daraja callback (which contains `MpesaReceiptNumber`) is POSTed to a Cloudflare Quick Tunnel URL. Quick Tunnels (`trycloudflare.com`) generate a **new random URL every time they start**, have no uptime guarantee, and frequently disconnect. When the tunnel is down or the URL is stale, Daraja's callback never reaches the server.

Additionally, a **race condition** existed: if the sync path marked the payment as `completed` before the callback arrived, the callback handler's idempotency check would return early (status is no longer `pending`), skipping the receipt extraction.

**Fixes:**

1. **Callback backfill logic** in `MpesaController::callback()`: If a callback arrives for a payment that is already `completed` but has `mpesa_receipt = NULL`, the handler now backfills the receipt and raw callback data instead of returning early.

2. **Defensive `mpesa_receipt` in sync path**: All three places in `InvoicePaymentController` where the sync path marks a payment as `completed` now include `'mpesa_receipt' => $queryResult['mpesa_receipt'] ?? $payment->mpesa_receipt` in the update data. This safely preserves any existing receipt value and is ready to populate it if a future API version includes it.

3. **Case-insensitive callback parsing** in `MpesaService::parseCallback()`: The parser now accepts both `$body['Body']['stkCallback']` and `$body['body']['stkCallback']` to handle any case variations from Daraja.

**Files:**
- `app/Http/Controllers/Payments/MpesaController.php`
- `app/Http/Controllers/InvoicePaymentController.php`
- `app/Services/MpesaService.php`

---

## 3. Architecture: How M-Pesa Payments Work

### Payment Flow

```
End-Client clicks "Pay with M-Pesa" on invoice payment page
        |
        v
InvoicePaymentController::processMpesa()
  - Validates phone number
  - Calls MpesaStkService::initiateSTKPush()
  - Creates Payment record (status: pending)
  - Returns checkoutRequestID to frontend
        |
        v
Daraja sends STK prompt to end-client's phone
        |
        v
   [Two parallel paths]
        |                           |
        v                           v
  PATH A: Callback              PATH B: Sync (Polling)
  Daraja POSTs to               Success page polls
  /api/payments/mpesa/callback  /pay/{id}/{token}/status
        |                           |
        v                           v
  MpesaController::callback()   InvoicePaymentController::checkPaymentStatus()
  - Parses Body.stkCallback     - Calls MpesaStkService::querySTKPushStatus()
  - Extracts MpesaReceiptNumber - Gets result_code (0 = success)
  - Updates payment:            - Updates payment:
    transaction_status=completed    transaction_status=completed
    mpesa_receipt=UB7A264PZB        mpesa_receipt=NULL (API limitation)
    raw_callback={full payload}     raw_callback={query result}
  - Allocates to invoice        - Allocates to invoice
  - Creates journal entries     - Creates journal entries
```

### Key Insight

| Data Source            | Returns `mpesa_receipt`? | Reliability      |
|------------------------|--------------------------|------------------|
| Daraja Callback (POST) | YES                      | Depends on tunnel |
| STK Query API (GET)    | NO                       | Always available  |

The **callback is the only source of `mpesa_receipt`**. If the tunnel is down, the receipt will remain NULL until the callback eventually arrives and backfills it.

---

## 4. File-by-File Changes

### `app/Http/Controllers/Payments/MpesaController.php`

| Section | Change |
|---------|--------|
| Idempotency check (line ~167) | Added backfill logic: if payment is `completed` but `mpesa_receipt` is NULL, and callback provides a receipt, update it along with `raw_callback` |
| Unknown payment log | Improved log message with SQL hint for debugging |

### `app/Http/Controllers/InvoicePaymentController.php`

| Section | Change |
|---------|--------|
| `syncInvoicePendingPaymentsWithDaraja()` | Added `mpesa_receipt` to `$updateData` with null-coalescing fallback |
| `checkPaymentStatus()` primary query block | Added `mpesa_receipt` to `$updateData` with null-coalescing fallback |
| `checkPaymentStatus()` 4999-retry block | Added `mpesa_receipt` to `$updateData` with null-coalescing fallback |
| `checkPaymentStatus()` all 3 blocks | Added `raw_callback` persistence with `_source` tag for audit trail |
| New method: `ensurePaymentHasMpesaAccount()` | Auto-creates COA 1400 (Cash) and M-Pesa Account if missing |

### `app/Services/MpesaService.php`

| Section | Change |
|---------|--------|
| `parseCallback()` | Changed to accept both `$body['Body']` and `$body['body']` (case-insensitive) |
| `parseCallback()` | Added warning log with `body_preview` and `body_keys` when parse fails |

### `app/Services/MpesaStkService.php`

| Section | Change |
|---------|--------|
| No code changes | Confirmed that `querySTKPushStatus()` returns only `result_code`, `result_desc`, `checkout_request_id`, `is_paid` -- no `mpesa_receipt` (Daraja API limitation) |

### `app/Services/PaymentService.php`

| Section | Change |
|---------|--------|
| `processPayment()` | Auto-create COA 1200 (Accounts Receivable) if missing for tenant |
| `processPayment()` | Auto-create COA 2200 (Unapplied Credits) if missing for tenant |

### `app/Services/InvoicePostingService.php`

| Section | Change |
|---------|--------|
| `postInvoice()` | Auto-create COA 1200 (Accounts Receivable) if missing for tenant |
| `postInvoice()` | Auto-create COA 4100 (Sales Revenue) if missing for tenant |
| `postInvoice()` | Auto-create COA 2200 (Tax Payable) if missing for tenant |

### `resources/views/invoice/payment/success.blade.php`

| Section | Change |
|---------|--------|
| Polling config | `reloadDelayMs`: 3000 -> 8000 (8 seconds) |
| Polling config | `maxReloads`: 40 -> 15 (max 2 minutes total) |

---

## 5. Database Migration

### `2026_01_23_121600_update_invoices_table_per_tenant_unique.php`

```php
// UP: Change global unique to per-tenant unique
Schema::table('invoices', function (Blueprint $table) {
    $table->dropUnique(['invoice_number']);
    $table->unique(['tenant_id', 'invoice_number']);
});

// DOWN: Revert to global unique
Schema::table('invoices', function (Blueprint $table) {
    $table->dropUnique(['tenant_id', 'invoice_number']);
    $table->unique(['invoice_number']);
});
```

**Run with:** `php artisan migrate`

---

## 6. Cloudflare Tunnel Setup

The Daraja sandbox requires a publicly accessible `CallBackURL`. In development, we use Cloudflare Quick Tunnels.

### Starting the Tunnel

```bash
# From the project root:
./cloudflared.exe tunnel --url http://localhost:8000
```

The tunnel will print a URL like:
```
https://encoding-composed-moved-helen.trycloudflare.com
```

### Updating `.env`

After starting the tunnel, **immediately** update `.env` with the new URL:

```env
MPESA_CALLBACK_BASE=https://<your-tunnel-url>.trycloudflare.com
MPESA_CALLBACK_URL=https://<your-tunnel-url>.trycloudflare.com/api/payments/mpesa/callback
```

Then clear config cache:

```bash
php artisan config:clear
```

### Verifying Tunnel Connectivity

Send a test POST to the callback test endpoint:

```powershell
Invoke-RestMethod -Uri "https://<your-tunnel-url>.trycloudflare.com/api/payments/mpesa/callback-test" -Method POST -ContentType "application/json" -Body '{"test": "tunnel_verify"}'
```

You should get `{"success": true, "message": "Callback test endpoint received your request", ...}`.

### Important Notes

- Quick Tunnel URLs are **random and change every restart** -- always update `.env` after restarting.
- Quick Tunnels have **no uptime guarantee** and may disconnect silently.
- For production, use a **named Cloudflare Tunnel** or a static domain (ngrok, etc.).

---

## 7. Known Limitations

### 7.1 Daraja STK Query Does Not Return Receipt Number

The Safaricom Daraja `stkpushquery/v1/query` endpoint returns only:
- `ResponseCode`, `ResponseDescription`
- `MerchantRequestID`, `CheckoutRequestID`
- `ResultCode`, `ResultDesc`

It does **NOT** return `MpesaReceiptNumber`. This means the sync/polling path can confirm payment success but cannot populate the `mpesa_receipt` column.

**Impact:** If the callback never arrives (tunnel down), `mpesa_receipt` will be NULL even though the payment is `completed`.

**Mitigation:** The callback backfill logic in `MpesaController` will populate the receipt whenever a callback eventually arrives, even if the payment was already marked as `completed` by the sync path.

### 7.2 Daraja Sandbox Rate Limiting

The sandbox enforces `5 requests per 60 seconds`. The success page polls every 8 seconds, which means roughly 7-8 queries per minute. The first query after an STK Push often hits a rate limit because the push itself counted as a request.

**Mitigation:** The 8-second interval and 15-reload cap keep the total well under the limit for a single payment session. Back-to-back STK queries may still occasionally return 429.

### 7.3 Sandbox Amount Mismatch

In sandbox mode, the STK Push sends `Amount: 1` (KES 1.00) regardless of the actual invoice amount. The callback returns `Amount: 1.00`. The system uses the **original invoice amount** from the `payments` table in development mode, not the callback amount.

---

## 8. Troubleshooting Guide

### Payment stuck on "Confirming..."

1. Check Laravel logs: `storage/logs/laravel.log`
2. Look for `Failed to sync payment from Daraja` errors.
3. Common cause: Missing Chart of Account (should now auto-create).
4. Check if Daraja returns 429 -- wait 60 seconds and refresh.

### `mpesa_receipt` is NULL

1. Check `raw_callback._source` in the `payments` table:
   - If `daraja_stk_query` -- the callback never arrived. Check tunnel.
   - If it contains `Body.stkCallback` -- the callback arrived but parsing may have failed.
2. Verify tunnel is running: `Get-Process cloudflared`
3. Verify `.env` URL matches current tunnel: compare `MPESA_CALLBACK_URL` with tunnel output.
4. Test tunnel: POST to `/api/payments/mpesa/callback-test`.

### Invoice payment link returns 404

1. Check if `payment_token` is set on the invoice in the database.
2. If NULL -- the invoice sending transaction rolled back. Check logs for `Invoice send failed`.
3. Common cause: Missing Chart of Account during posting (should now auto-create).
4. Re-send the invoice from the dashboard.

### Daraja returns "Invalid CallBackURL"

1. The `MPESA_CALLBACK_URL` in `.env` is not a valid HTTPS URL.
2. Ensure the tunnel is running and the URL is updated.
3. Run `php artisan config:clear` after updating `.env`.

---

## Chart of Accounts Auto-Created by the System

| Code | Name | Type | Category | Created By |
|------|------|------|----------|------------|
| 1200 | Accounts Receivable | asset | current_asset | InvoicePostingService, PaymentService |
| 1400 | Cash | asset | current_asset | InvoicePaymentController (ensurePaymentHasMpesaAccount) |
| 2200 | Tax Payable / Unapplied Credits | liability | current_liability | InvoicePostingService, PaymentService |
| 4100 | Sales Revenue | revenue | operating_revenue | InvoicePostingService |

> **Note:** These are only auto-created if missing for the tenant. If the accounts already exist, they are used as-is.
