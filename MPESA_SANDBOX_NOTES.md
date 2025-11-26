# M-Pesa Sandbox - Important Notes

## 📱 About M-Pesa Messages in Sandbox

### Why You Might Not Receive Messages

In M-Pesa **sandbox mode**, there are several reasons you might not receive confirmation messages:

1. **Sandbox Limitations:**
   - M-Pesa sandbox doesn't always send SMS messages
   - Some test transactions don't generate receipts
   - Messages may be delayed or not sent at all

2. **Test Amount (KES 1.00):**
   - Very small amounts may not trigger messages
   - Sandbox may skip notifications for test transactions

3. **Callback Blocked:**
   - If the callback URL is unreachable, M-Pesa thinks payment failed
   - No message is sent if callback isn't received

## ✅ How to Verify Payment

### Option 1: Check M-Pesa Statement
1. Open M-Pesa app on your phone
2. Go to "M-Pesa Statement" or "Transactions"
3. Look for transaction to "174379" (sandbox shortcode)
4. Amount: KES 1.00
5. Time: Should match when you entered PIN

### Option 2: Check M-Pesa Balance
- If KES 1.00 was deducted, payment went through
- Balance should be reduced by KES 1.00

### Option 3: Check System Logs
```bash
tail -f storage/logs/laravel.log | grep -i "mpesa\|callback"
```

### Option 4: Check Payment in Database
```bash
php artisan tinker --execute="
\$p = \App\Models\Payment::latest()->first();
echo 'Status: ' . \$p->transaction_status . PHP_EOL;
echo 'Receipt: ' . (\$p->mpesa_receipt ?? 'N/A') . PHP_EOL;
"
```

## 🔧 If Payment Completed But No Message

If you:
- ✅ Received STK push
- ✅ Entered PIN
- ✅ Money was deducted (check balance)
- ❌ But no message received

**This is normal in sandbox!** The payment likely went through, but:
- Callback may have been blocked or unreachable
- Message wasn't sent by sandbox
- Receipt may not appear

**Solution:** Process the callback manually:
```bash
./test-callback.sh <checkout_request_id>
```

## 🎯 Production vs Sandbox

**Sandbox:**
- May not send SMS messages
- Receipts may be delayed
- Callbacks may be blocked if URL is unreachable
- Test amount: KES 1.00

**Production:**
- Always sends SMS messages
- Real receipts immediately
- Callbacks work properly
- Full amount charged

## 💡 Best Practice

For development:
1. Complete STK push on phone
2. Check M-Pesa balance/statement to verify payment
3. Process callback manually if needed
4. Don't rely on SMS messages in sandbox

For production:
- Use Cloudflare Tunnel or static domain
- Callbacks work automatically
- Real messages and receipts always sent

