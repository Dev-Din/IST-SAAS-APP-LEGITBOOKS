#!/bin/bash

# Start Cloudflare Tunnel for M-Pesa Callbacks
# This creates a public URL without interstitial pages

echo "🌐 Starting Cloudflare Tunnel for M-Pesa Callbacks"
echo "=================================================="
echo ""

# Check if cloudflared is available
if command -v cloudflared &> /dev/null; then
    CLOUDFLARED_CMD="cloudflared"
elif [ -f ~/.local/bin/cloudflared ]; then
    CLOUDFLARED_CMD="$HOME/.local/bin/cloudflared"
    export PATH="$PATH:$HOME/.local/bin"
else
    echo "❌ cloudflared not found!"
    echo ""
    echo "Please install it first:"
    echo "  ./install-cloudflare-tunnel.sh"
    echo ""
    echo "Or manually:"
    echo "  wget https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64"
    echo "  chmod +x cloudflared-linux-amd64"
    echo "  sudo mv cloudflared-linux-amd64 /usr/local/bin/cloudflared"
    echo ""
    exit 1
fi

echo "✅ Found cloudflared"
echo ""
echo "🚀 Starting tunnel to http://localhost:5000"
echo ""
echo "📋 IMPORTANT:"
echo "   1. Copy the URL that appears below (e.g., https://xxxxx.trycloudflare.com)"
echo "   2. Update your .env file: MPESA_CALLBACK_URL=https://xxxxx.trycloudflare.com/api/payments/mpesa/callback"
echo "   3. Run: php artisan config:clear"
echo ""
echo "Press Ctrl+C to stop"
echo ""
echo "----------------------------------------"
echo ""

# Start tunnel
$CLOUDFLARED_CMD tunnel --url http://localhost:5000

