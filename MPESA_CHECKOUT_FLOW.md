# M-Pesa Checkout Flow Documentation

## Overview

This document describes the complete M-Pesa purchase flow for plan subscriptions in LegitBooks. The flow uses M-Pesa STK Push (Daraja API) with server-rendered Blade templates and AJAX polling for payment confirmation.

## Architecture

- **Frontend**: Server-rendered Blade templates with vanilla JavaScript for AJAX polling
- **Backend**: Laravel controllers and services
- **Payment Gateway**: M-Pesa Daraja API (Sandbox/Production)
- **Tunneling**: Cloudflare Tunnel for local development callbacks

## Routes

### Tenant Routes (Protected)

- `POST /app/checkout/{plan}/pay-mpesa` → `CheckoutController@payWithMpesa`
  - Initiates STK Push for plan purchase
  - Creates pending payment record
  - Returns waiting page with client token

- `GET /app/checkout/{plan}/mpesa-status/{token}` → `CheckoutController@mpesaStatus`
  - Polling endpoint for payment status
  - Returns JSON: `{status: 'pending'|'success'|'failed'}`

### API Routes (Public)

- `POST /api/payments/mpesa/callback` → `MpesaStkController@callback`
  - M-Pesa webhook endpoint
  - Processes payment confirmations
  - Activates subscriptions
  - Creates journal entries

## Flow Diagram

```
1. User clicks "Pay with M-Pesa" on plan checkout
   ↓
2. POST /app/checkout/{plan}/pay-mpesa
   - Validates plan and phone number
   - Creates Payment record (status: pending)
   - Generates client_token (UUID)
   - Initiates STK Push via MpesaStkService
   - Returns waiting page
   ↓
3. Waiting Page (Blade + JS)
   - Displays spinner and "Waiting for payment..."
   - Polls /app/checkout/{plan}/mpesa-status/{token} every 2s
   - Exponential backoff after 60s (max 10s interval)
   ↓
4. User completes STK Push on phone
   ↓
5. M-Pesa sends callback to /api/payments/mpesa/callback
   - Validates callback structure
   - Checks for Cloudflare challenges
   - Finds payment by checkout_request_id
   - Checks idempotency (already processed?)
   - Updates payment: status=completed, mpesa_receipt, etc.
   - Activates subscription (status=active)
   - Creates audit logs
   - Creates journal entries (Debit Bank, Credit Revenue)
   ↓
6. Next poll returns status: 'success'
   - JS stops polling
   - Shows success message
   - Redirects to /app/dashboard?payment=success
   ↓
7. Dashboard displays success alert
```

## Database Schema

### Payments Table

Key fields used:
- `client_token` (UUID) - Unique token for polling
- `checkout_request_id` - M-Pesa STK Push request ID
- `merchant_request_id` - M-Pesa merchant request ID
- `transaction_status` - 'pending'|'completed'|'failed'|'cancelled'
- `mpesa_receipt` - M-Pesa receipt number
- `raw_callback` - JSON of full callback payload
- `subscription_id` - Linked subscription

### Subscriptions Table

- `status` - 'pending'|'active'|'expired'|'cancelled'
- `plan` - 'starter'|'business'|'enterprise'
- `started_at` - Subscription start date
- `ends_at` - Subscription end date

## Configuration

### .env Variables

```env
# M-Pesa Configuration
MPESA_ENVIRONMENT=sandbox
MPESA_CONSUMER_KEY=your_consumer_key
MPESA_CONSUMER_SECRET=your_consumer_secret
MPESA_PASSKEY=your_passkey
MPESA_SHORTCODE=your_shortcode
MPESA_CALLBACK_BASE=https://your-cloudflare-url.trycloudflare.com
MPESA_CALLBACK_URL=${MPESA_CALLBACK_BASE}/api/payments/mpesa/callback
```

### config/mpesa.php

- `callback_base` - Base URL for callbacks (Cloudflare Tunnel URL)
- `callback_url` - Full callback URL
- `token_cache_ttl` - Access token cache TTL (3600s)

## Testing

### Manual Testing with Sandbox

1. **Start Cloudflare Tunnel**:
   ```bash
   cloudflared tunnel --url http://localhost:5000
   ```
   Copy the URL (e.g., `https://xxxxx.trycloudflare.com`)

2. **Update .env**:
   ```env
   MPESA_CALLBACK_BASE=https://xxxxx.trycloudflare.com
   MPESA_CALLBACK_URL=https://xxxxx.trycloudflare.com/api/payments/mpesa/callback
   ```

3. **Clear config cache**:
   ```bash
   php artisan config:clear
   ```

4. **Initiate Payment**:
   - Visit `/app/billing/page`
   - Select a plan (Starter or Business)
   - Enter phone number (e.g., `254712345678`)
   - Click "Pay with M-Pesa"
   - Complete STK Push on phone (KES 1.00 in dev)
   - Wait for automatic redirect

### Simulate Callback (Testing)

```bash
curl -X POST http://localhost:5000/api/payments/mpesa/callback \
  -H "Content-Type: application/json" \
  -d '{
    "Body": {
      "stkCallback": {
        "CheckoutRequestID": "YOUR_CHECKOUT_REQUEST_ID",
        "ResultCode": 0,
        "ResultDesc": "The service request is processed successfully.",
        "CallbackMetadata": {
          "Item": [
            {"Name": "Amount", "Value": 1.00},
            {"Name": "MpesaReceiptNumber", "Value": "TEST123456"},
            {"Name": "PhoneNumber", "Value": "254712345678"},
            {"Name": "TransactionDate", "Value": "20251127120000"}
          ]
        }
      }
    }
  }'
```

Replace `YOUR_CHECKOUT_REQUEST_ID` with the actual `checkout_request_id` from the payment record.

### PHPUnit Tests

```bash
php artisan test --filter MpesaCheckoutFlowTest
```

## Security & Cloudflare

### Cloudflare Challenge Detection

The callback handler detects Cloudflare challenges by checking for HTML in the payload:
- Detects `<!DOCTYPE`, `cf-challenge`, or `cloudflare` in body
- Logs error and returns 200 quickly to avoid retries
- Cloudflare headers are logged for debugging: `CF-RAY`, `cf-mitigated`

### Idempotency

- Payments are identified by `checkout_request_id` (unique)
- If payment `transaction_status` is already `completed`, callback returns 200 without reprocessing
- Prevents duplicate journal entries and subscription activations

### Callback Validation

- Validates callback structure (must have `Body.stkCallback`)
- Finds payment by `checkout_request_id` or `merchant_request_id`
- Returns 200 for unknown payments (avoids retries)

## Journal Entries

### Subscription Payments

For subscription payments, journal entries are created automatically:
- **Debit**: Bank/Cash (M-Pesa account) - Amount received
- **Credit**: Revenue account (code 4100) - Subscription revenue

### Invoice Payments

For invoice payments (existing flow):
- **Debit**: Bank/Cash
- **Credit**: Accounts Receivable (code 1200)

## Error Handling

### Polling Timeout

- After 5 minutes (150 polls), shows "I paid — check status" button
- User can manually check status
- Exponential backoff: 2s → 5s → 10s after 60 seconds

### Callback Failures

- Subscription activation failures are logged but don't fail payment
- Journal entry failures are logged but don't fail payment
- Payment is marked as `completed` even if post-processing fails
- Failed post-processing can be retried manually

## Logging

All M-Pesa operations are logged to `storage/logs/laravel.log`:

- STK Push initiation
- Callback receipt (with Cloudflare headers)
- Payment processing
- Subscription activation
- Journal entry creation
- Errors and exceptions

## Troubleshooting

### Callback Not Received

1. Check Cloudflare Tunnel is running
2. Verify `MPESA_CALLBACK_URL` in `.env`
3. Check Cloudflare logs for challenges
4. Verify callback URL is publicly reachable
5. Check M-Pesa Daraja app settings (callback URL must match exactly)

### Payment Stuck in Pending

1. Check `storage/logs/laravel.log` for callback errors
2. Manually simulate callback using curl (see Testing section)
3. Check payment `checkout_request_id` matches callback
4. Verify subscription activation in database

### Cloudflare Challenges

If callbacks are blocked by Cloudflare:
1. Check Cloudflare dashboard for security settings
2. Consider whitelisting M-Pesa IPs (see `config/mpesa.php`)
3. Use Cloudflare Tunnel with proper authentication
4. Check `cf-mitigated` header in logs

## Sample Payloads

### STK Push Request (MpesaStkService)

```json
{
  "BusinessShortCode": "174379",
  "Password": "base64_encoded_password",
  "Timestamp": "20251127120000",
  "TransactionType": "CustomerPayBillOnline",
  "Amount": 1,
  "PartyA": "254712345678",
  "PartyB": "174379",
  "PhoneNumber": "254712345678",
  "CallBackURL": "https://xxxxx.trycloudflare.com/api/payments/mpesa/callback",
  "AccountReference": "SUB-123-1234567890",
  "TransactionDesc": "Subscription payment for Starter plan"
}
```

### Callback Payload (M-Pesa → App)

```json
{
  "Body": {
    "stkCallback": {
      "CheckoutRequestID": "ws_CO_27112025120000_123456789",
      "ResultCode": 0,
      "ResultDesc": "The service request is processed successfully.",
      "CallbackMetadata": {
        "Item": [
          {"Name": "Amount", "Value": 1.00},
          {"Name": "MpesaReceiptNumber", "Value": "QHX12345678"},
          {"Name": "PhoneNumber", "Value": "254712345678"},
          {"Name": "TransactionDate", "Value": "20251127120000"}
        ]
      }
    }
  }
}
```

## Development Notes

- **Test Amount**: In development, STK Push charges KES 1.00, but payment record stores actual amount
- **Polling Interval**: 2 seconds initially, exponential backoff after 60s
- **Max Polling Time**: 5 minutes (150 polls)
- **Cache Busting**: Polling requests include `?_=timestamp` to avoid caching

## Production Checklist

- [ ] Update `MPESA_ENVIRONMENT=production` in `.env`
- [ ] Use production M-Pesa credentials
- [ ] Set `MPESA_CALLBACK_BASE` to production domain
- [ ] Configure Cloudflare security settings
- [ ] Test callback URL is reachable from M-Pesa servers
- [ ] Verify journal entry accounts exist (M-Pesa, Revenue)
- [ ] Monitor logs for callback failures
- [ ] Set up alerts for failed payments

