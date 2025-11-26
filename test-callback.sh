#!/bin/bash

# Test M-Pesa callback endpoint with sample payload
# Usage: ./test-callback.sh [checkout_request_id] [merchant_request_id]

echo "Testing M-Pesa Callback Endpoint"
echo "================================="
echo ""

# Get checkout request ID from argument or use default
CHECKOUT_ID=${1:-"ws_CO_26112025225439983719286858"}
MERCHANT_ID=${2:-"65c1-4675-96a1-ce3150ced5c6950"}

echo "Checkout Request ID: $CHECKOUT_ID"
echo "Merchant Request ID: $MERCHANT_ID"
echo ""

# Try to get payment details from database
echo "Checking for payment record..."
PAYMENT_INFO=$(php artisan tinker --execute="
\$payment = \App\Models\Payment::where('checkout_request_id', '$CHECKOUT_ID')->first();
if (\$payment) {
    echo 'Payment ID: ' . \$payment->id . PHP_EOL;
    echo 'Invoice ID: ' . (\$payment->invoice_id ?? 'N/A') . PHP_EOL;
    echo 'Amount: ' . \$payment->amount . PHP_EOL;
    echo 'Status: ' . \$payment->transaction_status . PHP_EOL;
    echo 'Phone: ' . (\$payment->phone ?? 'N/A') . PHP_EOL;
} else {
    echo 'Payment not found in database' . PHP_EOL;
    echo 'This is normal if testing without invoice' . PHP_EOL;
}
" 2>/dev/null)

echo "$PAYMENT_INFO"
echo ""

# Sample M-Pesa callback payload (successful payment)
PAYLOAD=$(cat <<EOF
{
  "Body": {
    "stkCallback": {
      "MerchantRequestID": "$MERCHANT_ID",
      "CheckoutRequestID": "$CHECKOUT_ID",
      "ResultCode": 0,
      "ResultDesc": "The service request is processed successfully.",
      "CallbackMetadata": {
        "Item": [
          {
            "Name": "Amount",
            "Value": 1
          },
          {
            "Name": "MpesaReceiptNumber",
            "Value": "TEST$(date +%s | tail -c 6)"
          },
          {
            "Name": "TransactionDate",
            "Value": $(date +%Y%m%d%H%M%S)
          },
          {
            "Name": "PhoneNumber",
            "Value": 254719286858
          }
        ]
      }
    }
  }
}
EOF
)

echo "Sending test callback..."
echo ""

# Get callback URL from config
CALLBACK_URL=$(php artisan tinker --execute="echo config('mpesa.callback_url');" 2>/dev/null)

if [ -z "$CALLBACK_URL" ]; then
    echo "❌ Error: MPESA_CALLBACK_URL not configured"
    echo "Please set MPESA_CALLBACK_URL in .env or config/mpesa.php"
    exit 1
fi

echo "Using callback URL: $CALLBACK_URL"
echo ""

RESPONSE=$(curl -s -X POST "$CALLBACK_URL" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "$PAYLOAD")

echo "Response:"
echo "$RESPONSE" | python3 -m json.tool 2>/dev/null || echo "$RESPONSE"
echo ""

# Check if payment was updated
echo "Checking payment status..."
php artisan tinker --execute="
\$payment = \App\Models\Payment::where('checkout_request_id', '$CHECKOUT_ID')->first();
if (\$payment) {
    echo 'Payment Status: ' . \$payment->transaction_status . PHP_EOL;
    echo 'M-Pesa Receipt: ' . (\$payment->mpesa_receipt ?? 'N/A') . PHP_EOL;
    if (\$payment->invoice_id) {
        \$invoice = \$payment->invoice;
        echo 'Invoice Status: ' . \$invoice->status . PHP_EOL;
        echo 'Invoice Outstanding: ' . \$invoice->getOutstandingAmount() . PHP_EOL;
    }
} else {
    echo 'Payment not found. This is normal if testing without invoice.' . PHP_EOL;
}
" 2>/dev/null

echo ""
echo "Check logs: tail -f storage/logs/laravel.log"

