# Sample M-Pesa Payment (Compliance Check)

Use these steps to run a real M-Pesa payment with your number and confirm the system captures it, marks the invoice paid, and shows the receipt.

## Prerequisites

1. **App running**  
   - `php artisan serve` (or your usual URL, e.g. `http://127.0.0.1:8000`).

2. **M-Pesa configured in `.env`** (sandbox or production):
   - `MPESA_CONSUMER_KEY`
   - `MPESA_CONSUMER_SECRET`
   - `MPESA_PASSKEY`
   - `MPESA_SHORTCODE`
   - For **local testing**, either:
     - Use a **tunnel** (e.g. Cloudflare tunnel, ngrok) and set `MPESA_CALLBACK_BASE` to the tunnel URL so Safaricom can reach your callback, **or**
     - Rely on **polling**: after you complete the STK on your phone, the success page will poll the status endpoint; the backend will query Daraja and update the payment when it sees it as paid (no callback needed).

3. **Test data**  
   - A **tenant** with an **invoice** that is **sent** (has a payment link), **contact** with a valid email, and optional **line items**.

## Steps to Do a Sample M-Pesa Payment with Your Number

1. **Get the payment link**
   - Log in to the **tenant** app (`/app`).
   - Go to **Invoices** and open an invoice that is **Sent** (or send one first).
   - Copy the **payment link** (e.g. `http://127.0.0.1:8000/pay/2/TOKEN_HERE`) or open it in a browser.

2. **Open the payment page**
   - In a browser (or incognito), open the payment URL.
   - You should see the invoice total and a field to enter **phone number**.

3. **Enter your M-Pesa number**
   - Use format **254XXXXXXXXX** (e.g. 254712345678).
   - Click **Pay with M-Pesa** (or equivalent button).

4. **Complete STK on your phone**
   - Your phone should receive the M-Pesa STK prompt.
   - Enter your M-Pesa PIN and confirm.
   - Wait for the “success” message on the phone.

5. **Confirm in the app**
   - The browser will either:
     - Redirect to the **success** page, then (after a short “Confirming your payment…” phase) redirect to the **receipt** page, **or**
     - Show “Confirming your payment…” and then redirect to the receipt when the backend sees the payment (via callback or status poll).
   - If it stays on “Payment is taking longer than expected”, click **Refresh** once; the backend will query Daraja and then redirect to the receipt.

6. **Verify compliance**
   - **Receipt page** loads and shows the correct invoice and payment.
   - In the **tenant portal**, open the same invoice and confirm:
     - **Status** is **Paid**.
     - **Payment** is listed (e.g. M-Pesa, amount, date).
   - Optionally use **Send Receipt** from the tenant invoice detail page and confirm the email arrives **with the receipt PDF attached**.

## Optional: Use Tunnel So Callback Works

If you want the callback to be hit (faster confirmation):

1. Start your tunnel (e.g. run `./cloudflared-tunnel.sh` or your ngrok command).
2. In `.env` set:
   - `MPESA_CALLBACK_BASE=https://your-tunnel-url`
   (The app will use this + `/api/payments/mpesa/callback` as the callback URL.)
3. Restart the app, then repeat the payment steps above.

## Troubleshooting

- **“Payment is taking longer than expected”**  
  Click **Refresh**. The success page will sync with Daraja and, if the payment is paid there, will update the invoice and redirect to the receipt.

- **“Contact does not have an email”**  
  Edit the contact in the tenant app and set a valid email so you can test “Send Receipt” and receive the PDF.

- **“No payment found”**  
  Ensure you used the same browser/session that started the payment (same `checkout_request_id` in the URL). If you closed the tab, use the payment link again and pay; that creates a new payment and you’ll get a new success URL.

- **Sandbox**  
  In Safaricom sandbox, use test credentials and the test phone numbers allowed by the sandbox documentation.
