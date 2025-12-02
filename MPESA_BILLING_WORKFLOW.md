# M-Pesa STK Push Billing Workflow Documentation

## Overview

This document describes the complete M-Pesa STK Push payment flow for subscription purchases in LegitBooks. The workflow includes frontend Blade integration, AJAX polling, backend STK processing, Cloudflare Tunnel handling, callback storage, subscription activation, and auto-redirect after payment confirmation.

## Architecture

### Flow Sequence

1. **Tenant selects plan** → Enters phone number
2. **STK Push sent** → M-Pesa Daraja API
3. **Page starts AJAX polling** → Every 2 seconds
4. **Callback arrives via Cloudflare** → Stored → Marks transaction successful
5. **Polling detects success** → Page auto reloads
6. **Tenant redirected to dashboard** → Success message displayed

## Endpoints

### 1. STK Push Initiation

**Endpoint:** `POST /app/{tenant_hash}/billing/mpesa/initiate`

**Authentication:** Required (`auth:web`)

**Request Body:**
```json
{
  "plan": "starter|business|enterprise",
  "phone": "2547XXXXXXXX"
}
```

**Response:**
```json
{
  "ok": true,
  "checkoutRequestID": "ws_CO_...",
  "message": "STK Push sent. Enter your M-Pesa PIN."
}
```

**Behaviors:**
- Validates phone format (2547XXXXXXXX)
- Resolves tenant & plan
- Creates payment record with `status = pending`
- Calls M-Pesa STK Push (Daraja)
- Stores CheckoutRequestID & MerchantRequestID
- Updates subscription to `pending` status

### 2. M-Pesa Callback

**Endpoint:** `POST /api/payments/mpesa/callback`

**Authentication:** None (public endpoint)

**Request Body:**
```json
{
  "Body": {
    "stkCallback": {
      "CheckoutRequestID": "...",
      "ResultCode": 0,
      "ResultDesc": "...",
      "CallbackMetadata": {
        "Item": [
          {"Name": "Amount", "Value": 1.00},
          {"Name": "MpesaReceiptNumber", "Value": "..."},
          {"Name": "PhoneNumber", "Value": "2547..."},
          {"Name": "TransactionDate", "Value": "20251202230000"}
        ]
      }
    }
  }
}
```

**Response:**
```json
{
  "ResultCode": 0,
  "ResultDesc": "Payment processed successfully"
}
```

**Behaviors:**
- Processes Cloudflare forwarded headers (CF-RAY, CF-Connecting-IP)
- Detects Cloudflare challenge pages and fails gracefully
- Extracts payment details from callback
- Updates payment by CheckoutRequestID
- If ResultCode = 0:
  - Marks transaction as `completed`
  - Activates tenant subscription
  - Creates/renews subscription record
  - Stores plan, duration, next billing date
  - Logs event
- If ResultCode != 0:
  - Marks transaction as `failed`
  - Stores raw callback JSON in `raw_callback` column

### 3. AJAX Polling Endpoint

**Endpoint:** `GET /app/{tenant_hash}/billing/mpesa/status/{checkoutRequestID}`

**Authentication:** Required (`auth:web`)

**Response:**
```json
{
  "status": "pending|completed|failed",
  "transaction": {
    "id": 1,
    "amount": 2500.00,
    "payment_number": "PAY-...",
    "mpesa_receipt": "..."
  },
  "subscription_active": true
}
```

**Behaviors:**
- Looks up payment by CheckoutRequestID
- If payment is still pending, queries Daraja API to get latest status
- Returns current payment status and subscription state

**Polling Rules:**
- Polls every 2 seconds
- Timeout at 5 minutes (150 polls)
- Stops immediately on success or failed

## Frontend Implementation

### Billing Page (`resources/views/tenant/billing/page.blade.php`)

**Key Features:**
- M-Pesa form with phone number input (format: 2547XXXXXXXX)
- AJAX submission to STK initiation endpoint
- Processing card display during payment
- Automatic polling for payment status
- Auto-redirect on success

**JavaScript Flow:**
1. User selects M-Pesa payment method
2. User enters phone number
3. Form submits via AJAX to `/billing/mpesa/initiate`
4. On success, shows processing card
5. Starts polling `/billing/mpesa/status/{checkoutRequestID}` every 2 seconds
6. On payment completion:
   - Stops polling
   - Updates UI to show success
   - Redirects to dashboard with `?paid=1` query parameter

### Dashboard Success Message

**Route:** `GET /app/{tenant_hash}/dashboard?paid=1`

**Display:**
- Green success alert: "Payment received. Subscription activated successfully."
- Dismissible with close button

## Database Schema

### Payments Table

Required fields:
- `id`
- `tenant_id`
- `subscription_id`
- `plan_id` (not used, subscription has plan)
- `phone`
- `amount`
- `checkout_request_id` (unique)
- `merchant_request_id`
- `status` (via `transaction_status` enum: pending, completed, failed, cancelled)
- `mpesa_receipt`
- `result_code` (stored in `raw_callback` JSON)
- `result_desc` (stored in `raw_callback` JSON)
- `callback_payload` (stored in `raw_callback` JSON column)
- `created_at`, `updated_at`

### Subscriptions Table

Required fields:
- `tenant_id`
- `plan` (plan_starter, plan_business, plan_enterprise)
- `starts_at`
- `ends_at`
- `status` (active, trial, cancelled, expired, pending)
- `next_billing_at`

## Validation & Security

### Phone Number Validation

**Regex:** `/^2547\d{8}$/`

**Examples:**
- ✅ Valid: `254712345678`
- ❌ Invalid: `0712345678`, `+254712345678`, `25471234567`

### Amount Validation

- Must match plan pricing:
  - Starter: KES 2,500
  - Business: KES 5,000
  - Enterprise: Custom (requires manual processing)

### Callback Idempotency

- Callbacks are processed based on `CheckoutRequestID`
- Duplicate callbacks are handled gracefully
- Payment status is only updated if still `pending`

### Route Protection

**Tenant Routes:**
- `auth:web` - User must be authenticated
- `ResolveTenant` - Tenant context must be resolved
- `EnsureTenantActive` - Tenant must be active

**Callback Route:**
- Public (no authentication)
- IP validation in production only
- Cloudflare header logging for troubleshooting

## Cloudflare Tunnel Setup

### Configuration

**Environment Variables:**
```env
MPESA_CALLBACK_BASE=https://xxxxx.trycloudflare.com
MPESA_CALLBACK_URL=https://xxxxx.trycloudflare.com/api/payments/mpesa/callback
```

### Running Tunnel

1. Start Laravel server:
   ```bash
   php artisan serve --host=127.0.0.1 --port=5000
   ```

2. Start Cloudflare tunnel:
   ```bash
   ./cloudflared-tunnel.sh
   ```

3. Copy tunnel URL and update `.env`:
   ```env
   MPESA_CALLBACK_BASE=https://your-tunnel-url.trycloudflare.com
   ```

### Troubleshooting

- **Invalid CallBackURL Error:** Ensure tunnel is running and URL is correct
- **Cloudflare Challenge:** Check tunnel logs for HTML challenge pages
- **Callback Not Received:** Verify tunnel is forwarding to correct port (5000)

## Testing

### Running Tests

```bash
php artisan test --filter=MpesaBillingWorkflowTest
```

### Test Coverage

1. **STK Initiation Test**
   - Asserts transaction is created
   - Asserts CheckoutRequestID stored
   - Asserts subscription updated to pending

2. **Callback Success Test**
   - Sends fake callback payload
   - Asserts transaction = completed
   - Asserts subscription activated

3. **Callback Failure Test**
   - Asserts transaction = failed
   - Asserts subscription remains pending

4. **Polling Endpoint Test**
   - Returns success when transaction is updated
   - Queries Daraja API if still pending

5. **Phone Validation Test**
   - Enforces format validation
   - Rejects invalid formats

## Local Testing with Cloudflare Tunnel

### Prerequisites

1. Laravel server running on port 5000
2. Cloudflare tunnel active
3. M-Pesa sandbox credentials configured

### Steps

1. **Start Services:**
   ```bash
   # Terminal 1: Laravel server
   php artisan serve --host=127.0.0.1 --port=5000
   
   # Terminal 2: Cloudflare tunnel
   ./cloudflared-tunnel.sh
   ```

2. **Update .env:**
   ```env
   MPESA_CALLBACK_BASE=https://your-tunnel-url.trycloudflare.com
   MPESA_ENVIRONMENT=sandbox
   ```

3. **Test Flow:**
   - Navigate to `/app/{tenant_hash}/billing/page`
   - Select a plan (Starter or Business)
   - Choose M-Pesa payment method
   - Enter phone: `254712345678`
   - Submit payment
   - Complete STK push on phone
   - Verify callback is received
   - Verify subscription is activated
   - Verify redirect to dashboard with success message

### Simulating Callbacks

```bash
curl -X POST https://your-tunnel-url.trycloudflare.com/api/payments/mpesa/callback \
  -H "Content-Type: application/json" \
  -d '{
    "Body": {
      "stkCallback": {
        "CheckoutRequestID": "ws_CO_TEST123",
        "ResultCode": 0,
        "ResultDesc": "The service request is processed successfully.",
        "CallbackMetadata": {
          "Item": [
            {"Name": "Amount", "Value": 1.00},
            {"Name": "MpesaReceiptNumber", "Value": "TEST123456"},
            {"Name": "PhoneNumber", "Value": "254712345678"},
            {"Name": "TransactionDate", "Value": "20251202230000"}
          ]
        }
      }
    }
  }'
```

## Error Handling

### Common Errors

1. **"Invalid CallBackURL"**
   - **Cause:** Cloudflare tunnel not running or URL incorrect
   - **Fix:** Start tunnel and update `.env`

2. **"Phone number must be in format 2547XXXXXXXX"**
   - **Cause:** Invalid phone format
   - **Fix:** Use format: 2547XXXXXXXX (e.g., 254712345678)

3. **"M-Pesa is not configured"**
   - **Cause:** Missing M-Pesa credentials
   - **Fix:** Set `MPESA_CONSUMER_KEY`, `MPESA_CONSUMER_SECRET`, `MPESA_PASSKEY`, `MPESA_SHORTCODE` in `.env`

4. **Payment stuck in pending**
   - **Cause:** Callback not received or failed
   - **Fix:** Use polling endpoint which queries Daraja API, or manually sync with `php artisan mpesa:sync-pending`

## Logging

All M-Pesa operations are logged:

- **STK Initiation:** `Log::info('M-Pesa STK Push initiated for subscription')`
- **Callback Received:** `Log::info('M-Pesa callback received')`
- **Payment Completed:** `Log::info('M-Pesa payment processed successfully')`
- **Subscription Activated:** `Log::info('Subscription activated via M-Pesa payment')`
- **Errors:** `Log::error('M-Pesa ... error')`

Check logs at `storage/logs/laravel.log`.

## Production Considerations

1. **IP Whitelist:** Enable IP validation for callbacks
2. **HTTPS:** Use HTTPS for callback URL
3. **Monitoring:** Monitor callback success rate
4. **Retry Logic:** Implement retry for failed callbacks
5. **Rate Limiting:** Add rate limiting to polling endpoint
6. **Queue Processing:** Consider queueing callback processing for high volume

## Support

For issues or questions:
1. Check logs: `storage/logs/laravel.log`
2. Verify Cloudflare tunnel is running
3. Test callback endpoint manually
4. Check M-Pesa Daraja API status

