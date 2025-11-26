#!/bin/bash

# Start Cloudflare Tunnel for M-Pesa Callbacks
# This script starts the tunnel and displays the URL

echo "🌐 Starting Cloudflare Tunnel"
echo "============================="
echo ""

# Add cloudflared to PATH if installed locally
export PATH="$PATH:$HOME/.local/bin:/usr/local/bin"

# Find cloudflared
if command -v cloudflared &> /dev/null; then
    CLOUDFLARED="cloudflared"
elif [ -f ~/.local/bin/cloudflared ]; then
    CLOUDFLARED="$HOME/.local/bin/cloudflared"
elif [ -f /usr/local/bin/cloudflared ]; then
    CLOUDFLARED="/usr/local/bin/cloudflared"
else
    echo "❌ cloudflared not found!"
    echo ""
    echo "Please install it first:"
    echo "  ./install-cloudflare-tunnel.sh"
    echo ""
    exit 1
fi

echo "✅ Using: $CLOUDFLARED"
echo ""

# Check if Laravel server is running on port 5000
if ! curl -s http://localhost:5000 > /dev/null 2>&1; then
    echo "⚠️  Warning: Laravel server doesn't appear to be running on port 5000"
    echo "   Make sure to start it with: ./serve-5000.sh"
    echo ""
fi

echo "🚀 Starting tunnel to http://localhost:5000"
echo ""
echo "📋 IMPORTANT STEPS:"
echo "   1. Wait for the URL to appear below (e.g., https://xxxxx.trycloudflare.com)"
echo "   2. Copy that URL"
echo "   3. Update your .env file:"
echo "      MPESA_CALLBACK_URL=https://xxxxx.trycloudflare.com/api/payments/mpesa/callback"
echo "   4. Run: php artisan config:clear"
echo ""
echo "Press Ctrl+C to stop the tunnel"
echo ""
echo "----------------------------------------"
echo ""

# Start tunnel
$CLOUDFLARED tunnel --url http://localhost:5000

