# M-Pesa STK Push - Local Development Guide

This guide explains how to test M-Pesa STK Push locally using Cloudflare Tunnel for development purposes.

## Prerequisites

1. **Cloudflare Tunnel** (cloudflared) installed and running
2. **Laravel server** running on port 5000
3. **M-Pesa sandbox credentials** configured in `.env`

## Quick Start

### 1. Start Your Local Server

```bash
# Option 1: Use the helper script
./serve-5000.sh

# Option 2: Manual
php artisan serve --port=5000 --host=127.0.0.1
```

### 2. Start Cloudflare Tunnel

```bash
./cloudflared-tunnel.sh
```

Copy the HTTPS URL (e.g., `https://random-words-1234.trycloudflare.com`)

### 3. Update Callback URL

Update `config/mpesa.php` or `.env`:

```php
// config/mpesa.php
'callback_url' => env('MPESA_CALLBACK_URL', env('APP_URL') . '/api/payments/mpesa/callback'),
```

Or in `.env`:
```env
MPESA_CALLBACK_URL=https://your-cloudflare-url.trycloudflare.com/api/payments/mpesa/callback
```

Then clear config cache:
```bash
php artisan config:clear
```

### 4. Test STK Push

#### Option A: Using the Full Flow Script (Recommended)

```bash
# Test with phone number only
./test-mpesa-full-flow.sh 254719286858

# Test with invoice
./test-mpesa-full-flow.sh 254719286858 2
```

#### Option B: Using the Direct Test Script

```bash
php test-stk-push.php
```

#### Option C: Using API Endpoint

```bash
curl -X POST http://localhost:5000/api/payments/mpesa/stk-push \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "invoice_id": 2,
    "phone_number": "254719286858"
  }'
```

### 5. Complete Payment on Phone

After initiating STK push, you'll receive a prompt on your phone. Complete the payment.

### 6. Handle Callback

**✅ With Cloudflare Tunnel:** Callbacks work automatically! M-Pesa will send the callback and it will be received by your server.

**⚠️ If callbacks aren't working:** You can manually simulate the callback for testing.

#### Option A: Automatic (Recommended with Cloudflare Tunnel)

If Cloudflare Tunnel is properly configured, callbacks are received automatically. Check logs to verify:

```bash
tail -f storage/logs/laravel.log | grep -i "callback"
```

#### Option B: Manual Callback Simulation (For Testing)

If needed, manually simulate the callback:

```bash
# Get the checkout request ID from logs or STK push response
./test-callback.sh ws_CO_26112025225439983719286858
```

Check logs for checkout request ID:
```bash
tail -50 storage/logs/laravel.log | grep "checkout_request_id"
```

## Understanding the Flow

1. **STK Push Initiation** ✅ Works perfectly
   - Your app sends STK push request to M-Pesa
   - M-Pesa sends prompt to user's phone
   - User receives and can complete payment

2. **Callback from M-Pesa** ✅ Works with Cloudflare Tunnel
   - M-Pesa sends callback to your configured URL
   - Cloudflare Tunnel forwards it to your local server
   - No interstitial page blocking
   - Callback is received automatically

3. **Manual Callback Simulation** ✅ Solution for local dev
   - After completing payment, manually trigger callback
   - Use `test-callback.sh` script
   - Simulates what M-Pesa would send

## Testing Checklist

- [ ] Server running on port 5000
- [ ] Cloudflare Tunnel active
- [ ] Callback URL updated in config
- [ ] STK push initiated successfully
- [ ] Payment completed on phone
- [ ] Callback received (automatic with Cloudflare Tunnel)
- [ ] Payment record created in database
- [ ] Invoice status updated (if applicable)
- [ ] Journal entries created (if applicable)

## Monitoring

### Watch Logs in Real-Time

```bash
tail -f storage/logs/laravel.log | grep -i "mpesa\|callback"
```

### Check Payment Records

```bash
php artisan tinker --execute="
\$payments = \App\Models\Payment::latest()->take(5)->get();
foreach (\$payments as \$p) {
    echo 'ID: ' . \$p->id . ' | Status: ' . \$p->transaction_status . ' | Amount: ' . \$p->amount . PHP_EOL;
}
"
```

### Check Invoice Status

```bash
php artisan tinker --execute="
\$invoice = \App\Models\Invoice::find(2);
echo 'Invoice: ' . \$invoice->invoice_number . PHP_EOL;
echo 'Status: ' . \$invoice->status . PHP_EOL;
echo 'Outstanding: ' . \$invoice->getOutstandingAmount() . PHP_EOL;
"
```

## Troubleshooting

### STK Push Not Received

1. Check phone number format (must be 254XXXXXXXXX)
2. Verify M-Pesa credentials in `.env`
3. Check logs for errors: `tail -f storage/logs/laravel.log`
4. Ensure phone number is registered in M-Pesa sandbox

### Callback Not Working

1. **Verify Cloudflare Tunnel is running** and URL is correct
2. **Solution:** Use `test-callback.sh` to simulate manually if needed
3. Verify endpoint is accessible: `curl -X POST https://your-cloudflare-url.trycloudflare.com/api/payments/mpesa/callback`
4. Check logs for callback attempts: `tail -f storage/logs/laravel.log | grep -i callback`

### Payment Record Not Created

1. Check if STK push was initiated through API endpoint (not direct service)
2. Verify invoice exists and has payment token
3. Check database for payment records

## Production Deployment

For production, you should:

1. **Use a static domain** or deploy to a server with public IP
2. **Register callback URL** in M-Pesa Daraja app settings
3. **Enable IP validation** in production
4. **Use real M-Pesa credentials** (not sandbox)
5. **Ensure HTTPS** is properly configured

## Files Reference

- `test-stk-push.php` - Direct STK push test
- `test-callback.sh` - Simulate M-Pesa callback
- `test-mpesa-full-flow.sh` - Complete flow test script
- `serve-5000.sh` - Start server on port 5000
- `cloudflared-tunnel.sh` - Start Cloudflare Tunnel

## Support

For issues:
1. Check logs: `storage/logs/laravel.log`
2. Verify M-Pesa credentials
3. Test endpoint accessibility
4. Review callback simulation

