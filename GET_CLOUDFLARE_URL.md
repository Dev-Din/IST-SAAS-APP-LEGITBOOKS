# Get Cloudflare Tunnel Callback URL - Quick Guide

## Step-by-Step Procedure

### Step 1: Start Laravel Server
```bash
./serve-5000.sh
```
Or manually:
```bash
php artisan serve --port=5000 --host=127.0.0.1
```

### Step 2: Start Cloudflare Tunnel
```bash
./cloudflared-tunnel.sh
```

### Step 3: Copy the URL
You'll see output like:
```
+--------------------------------------------------------------------------------------------+
|  Your quick Tunnel has been created! Visit it at (it may take some time to be reachable): |
|  https://random-words-1234.trycloudflare.com                                               |
+--------------------------------------------------------------------------------------------+
```

**Copy the URL:** `https://random-words-1234.trycloudflare.com`

### Step 4: Add `/api/payments/mpesa/callback` to the URL
Your callback URL will be:
```
https://random-words-1234.trycloudflare.com/api/payments/mpesa/callback
```

### Step 5: Update Configuration

**Option A: Update .env file**
```bash
MPESA_CALLBACK_URL=https://random-words-1234.trycloudflare.com/api/payments/mpesa/callback
```

**Option B: Update config/mpesa.php**
```php
'callback_url' => env('MPESA_CALLBACK_URL', 'https://random-words-1234.trycloudflare.com/api/payments/mpesa/callback'),
```

### Step 6: Clear Config Cache
```bash
php artisan config:clear
```

## ⚠️ Important Notes

- **URL changes each time** you restart Cloudflare Tunnel
- **Update the callback URL** in config each time you restart
- **Keep both terminals open** (server + tunnel)
- **Test the URL** by visiting it in browser (should show Laravel app)

## Quick Test

After setting up, test if callback URL is reachable:
```bash
curl -X POST https://your-cloudflare-url.trycloudflare.com/api/payments/mpesa/callback
```

You should get a response (even if it's an error - that means the URL is reachable).

