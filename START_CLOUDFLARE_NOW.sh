#!/bin/bash

# Quick start script for Cloudflare Tunnel
# This adds the PATH and starts the tunnel

export PATH="$PATH:$HOME/.local/bin"

echo "🌐 Starting Cloudflare Tunnel for M-Pesa"
echo "========================================="
echo ""

# Check if cloudflared exists
if [ -f ~/.local/bin/cloudflared ]; then
    CLOUDFLARED="$HOME/.local/bin/cloudflared"
elif command -v cloudflared &> /dev/null; then
    CLOUDFLARED="cloudflared"
else
    echo "❌ cloudflared not found!"
    echo "Installing..."
    wget -q https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64 -O /tmp/cloudflared
    chmod +x /tmp/cloudflared
    mkdir -p ~/.local/bin
    mv /tmp/cloudflared ~/.local/bin/cloudflared
    CLOUDFLARED="$HOME/.local/bin/cloudflared"
    export PATH="$PATH:$HOME/.local/bin"
fi

echo "✅ Using: $CLOUDFLARED"
$CLOUDFLARED --version | head -1
echo ""

# Check if server is running
if ! curl -s http://localhost:5000 > /dev/null 2>&1; then
    echo "⚠️  Warning: Laravel server may not be running on port 5000"
    echo "   Start it with: ./serve-5000.sh"
    echo ""
fi

echo "🚀 Starting tunnel..."
echo ""
echo "📋 NEXT STEPS:"
echo "   1. Copy the URL that appears below"
echo "   2. Update .env: MPESA_CALLBACK_URL=https://your-url.trycloudflare.com/api/payments/mpesa/callback"
echo "   3. Run: php artisan config:clear"
echo ""
echo "Press Ctrl+C to stop"
echo "----------------------------------------"
echo ""

$CLOUDFLARED tunnel --url http://localhost:5000

