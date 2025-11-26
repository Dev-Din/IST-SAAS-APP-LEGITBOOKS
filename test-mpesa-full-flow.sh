#!/bin/bash

# Complete M-Pesa STK Push Testing Script for Local Development
# This script helps test the full M-Pesa flow locally with Cloudflare Tunnel

echo "=========================================="
echo "M-Pesa STK Push - Local Development Test"
echo "=========================================="
echo ""

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Check if phone number is provided
if [ -z "$1" ]; then
    echo -e "${YELLOW}Usage: ./test-mpesa-full-flow.sh <phone_number> [invoice_id]${NC}"
    echo ""
    echo "Example: ./test-mpesa-full-flow.sh 254719286858"
    echo "Example: ./test-mpesa-full-flow.sh 254719286858 2"
    echo ""
    exit 1
fi

PHONE_NUMBER=$1
INVOICE_ID=${2:-""}

echo -e "${GREEN}Phone Number:${NC} $PHONE_NUMBER"
if [ -n "$INVOICE_ID" ]; then
    echo -e "${GREEN}Invoice ID:${NC} $INVOICE_ID"
else
    echo -e "${YELLOW}No invoice ID provided - will use test amount${NC}"
fi
echo ""

# Step 1: Initiate STK Push
echo -e "${YELLOW}Step 1: Initiating STK Push...${NC}"
echo ""

if [ -n "$INVOICE_ID" ]; then
    # Use API endpoint with invoice
    RESPONSE=$(curl -s -X POST http://localhost:5000/api/payments/mpesa/stk-push \
        -H "Content-Type: application/json" \
        -H "Accept: application/json" \
        -d "{
            \"invoice_id\": $INVOICE_ID,
            \"phone_number\": \"$PHONE_NUMBER\"
        }")
else
    # Use direct service test
    RESPONSE=$(php test-stk-push.php 2>&1 | tail -20)
    echo "$RESPONSE"
    echo ""
    echo -e "${GREEN}✓ STK Push initiated!${NC}"
    echo ""
    echo -e "${YELLOW}Please complete the payment on your phone now.${NC}"
    echo ""
    read -p "Press Enter after you've completed the payment on your phone..."
    
    # Extract checkout request ID from logs or use a default
    echo ""
    echo -e "${YELLOW}Step 2: Simulating M-Pesa Callback...${NC}"
    echo ""
    echo "If callbacks aren't working, you can simulate it manually."
    echo ""
    
    # Get the latest checkout request ID from logs
    CHECKOUT_ID=$(tail -50 storage/logs/laravel.log | grep -oP 'checkout_request_id":"\K[^"]+' | tail -1)
    
    if [ -z "$CHECKOUT_ID" ]; then
        echo -e "${RED}Could not find checkout request ID in logs.${NC}"
        echo "Please provide the Checkout Request ID from the STK push response:"
        read -p "Checkout Request ID: " CHECKOUT_ID
    else
        echo -e "${GREEN}Found Checkout Request ID:${NC} $CHECKOUT_ID"
    fi
    
    echo ""
    echo "Sending simulated callback..."
    ./test-callback.sh "$CHECKOUT_ID"
    
    exit 0
fi

# Parse response
SUCCESS=$(echo "$RESPONSE" | grep -o '"success":true' || echo "")
CHECKOUT_ID=$(echo "$RESPONSE" | grep -oP '"checkoutRequestID":"\K[^"]+' || echo "")

if [ -n "$SUCCESS" ] && [ -n "$CHECKOUT_ID" ]; then
    echo -e "${GREEN}✓ STK Push initiated successfully!${NC}"
    echo ""
    echo "Checkout Request ID: $CHECKOUT_ID"
    echo ""
    echo -e "${YELLOW}📱 Please check your phone ($PHONE_NUMBER) and complete the payment.${NC}"
    echo ""
    echo "After completing the payment:"
    echo "1. M-Pesa will send a callback to your configured URL"
    echo "2. If callbacks aren't working, you can manually simulate using:"
    echo "   ./test-callback.sh $CHECKOUT_ID"
    echo ""
    echo "Or wait and check logs: tail -f storage/logs/laravel.log"
    echo ""
else
    echo -e "${RED}✗ Failed to initiate STK Push${NC}"
    echo "Response: $RESPONSE"
    exit 1
fi

