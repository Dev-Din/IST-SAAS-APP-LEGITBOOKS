# Cloudflare Tunnel Setup - Complete Guide

## ✅ Installation Complete!

Cloudflare Tunnel is now installed and ready to use.

## 🚀 Quick Start (3 Steps)

### Step 1: Start Laravel Server

**Terminal 1:**

```bash
./serve-5000.sh
```

Keep this running.

### Step 2: Start Cloudflare Tunnel

**Terminal 2:**

```bash
./start-cloudflare-tunnel.sh
```

You'll see output like:

```
+--------------------------------------------------------------------------------------------+
|  Your quick Tunnel has been created! Visit it at:                                         |
|  https://random-words-1234.trycloudflare.com                                               |
+--------------------------------------------------------------------------------------------+
```

**📋 COPY THIS URL!** (e.g., `https://random-words-1234.trycloudflare.com`)

### Step 3: Update Callback URL

```bash
# Edit .env file
nano .env

# Add or update this line (replace with your Cloudflare URL):
MPESA_CALLBACK_URL=https://your-cloudflare-url.trycloudflare.com/api/payments/mpesa/callback

# Save (Ctrl+X, then Y, then Enter)

# Clear config cache
php artisan config:clear

# Verify it's set correctly
php artisan tinker --execute="echo config('mpesa.callback_url');"
```

## ✅ Test It Now!

1. Go to: `http://localhost:8000/app/billing/page`
2. Select a plan (Starter/Business)
3. Choose M-Pesa payment
4. Enter phone: `254719286858`
5. Click "Complete Payment"
6. **Check your phone** - STK push will arrive
7. Enter your M-Pesa PIN
8. **You'll now receive:**
    - ✅ Real M-Pesa confirmation message
    - ✅ Real receipt number on your phone
    - ✅ Automatic callback (no manual processing!)
    - ✅ Subscription activated automatically

## 📱 What You'll See

**On Your Phone:**

-   M-Pesa prompt: "Pay KES 1.00 to 174379?"
-   After PIN: "You have paid KES 1.00 to 174379. New M-Pesa balance..."
-   Receipt: "RFT1234567890" (real M-Pesa receipt number)
-   Transaction appears in M-Pesa statement

**In Your App:**

-   Payment automatically processed
-   Subscription activated
-   No manual steps needed!

## 🔍 Verify It's Working

**Check logs in real-time:**

```bash
tail -f storage/logs/laravel.log | grep -i "callback\|mpesa"
```

You should see:

```
M-Pesa callback received
M-Pesa payment processed successfully
Subscription activated via M-Pesa payment
```

**Check payment status:**

```bash
php artisan tinker --execute="
\$p = \App\Models\Payment::latest()->first();
echo 'Status: ' . \$p->transaction_status . PHP_EOL;
echo 'Receipt: ' . (\$p->mpesa_receipt ?? 'N/A') . PHP_EOL;
echo 'Subscription: ' . (\$p->subscription_id ?? 'N/A') . PHP_EOL;
"
```

## ⚠️ Important Notes

1. **Keep Tunnel Running:** Don't close the terminal running Cloudflare Tunnel
2. **URL Changes:** Each restart gives a new URL - update callback URL each time
3. **Sandbox Amount:** Charges KES 1.00 (test amount) but sends real receipts
4. **Receipt Timing:** May take 1-2 minutes to appear on phone in sandbox

## 🆘 Troubleshooting

**No callback received?**

-   Verify Cloudflare Tunnel is running
-   Check callback URL matches Cloudflare URL exactly
-   Verify Laravel server is on port 5000
-   Check logs: `tail -f storage/logs/laravel.log`

**Tunnel won't start?**

-   Make sure Laravel server is running first
-   Check port 5000 is not in use: `lsof -i:5000`
-   Verify cloudflared is installed: `cloudflared --version`

**URL keeps changing?**

-   This is normal for free Cloudflare Tunnel
-   Update callback URL each time you restart
-   For stable URL, consider Cloudflare account setup (optional)

## 🎉 Success!

Once set up, M-Pesa callbacks work automatically:

-   ✅ No more manual callback processing
-   ✅ Real M-Pesa receipts on your phone
-   ✅ Automatic subscription activation
-   ✅ Full end-to-end payment flow
