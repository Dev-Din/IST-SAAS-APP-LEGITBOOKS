#!/bin/bash

# Simulate M-Pesa STK callback for testing
# Usage: ./test-mpesa-callback.sh [checkout_request_id] [amount] [phone]

set -e

CHECKOUT_REQUEST_ID=${1:-"ws_CO_1234567890"}
AMOUNT=${2:-"1.00"}
PHONE=${3:-"254712345678"}

CALLBACK_URL="http://127.0.0.1:5000/api/payments/mpesa/callback"

# Generate callback payload
PAYLOAD=$(cat <<EOF
{
  "Body": {
    "stkCallback": {
      "CheckoutRequestID": "${CHECKOUT_REQUEST_ID}",
      "MerchantRequestID": "12345-67890-1",
      "ResultCode": 0,
      "ResultDesc": "The service request is processed successfully.",
      "CallbackMetadata": {
        "Item": [
          {
            "Name": "Amount",
            "Value": ${AMOUNT}
          },
          {
            "Name": "MpesaReceiptNumber",
            "Value": "RFT$(date +%s)"
          },
          {
            "Name": "PhoneNumber",
            "Value": "${PHONE}"
          },
          {
            "Name": "TransactionDate",
            "Value": "$(date +%Y%m%d%H%M%S)"
          }
        ]
      }
    }
  }
}
EOF
)

echo "Sending M-Pesa callback to: ${CALLBACK_URL}"
echo "Payload:"
echo "${PAYLOAD}" | jq .
echo ""

# Send callback
curl -X POST "${CALLBACK_URL}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "${PAYLOAD}" \
  -v

echo ""
echo "Done!"

