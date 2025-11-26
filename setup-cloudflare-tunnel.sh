#!/bin/bash

# Setup Cloudflare Tunnel for M-Pesa Callbacks (Free, No Interstitial)
# Cloudflare Tunnel provides a reliable way to expose local server for M-Pesa callbacks

echo "🌐 Setting up Cloudflare Tunnel for M-Pesa Callbacks"
echo "===================================================="
echo ""

# Check if cloudflared is installed
if ! command -v cloudflared &> /dev/null; then
    echo "❌ cloudflared is not installed"
    echo ""
    echo "Install it:"
    echo "  Linux: wget https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64 -O /usr/local/bin/cloudflared && chmod +x /usr/local/bin/cloudflared"
    echo "  Or visit: https://developers.cloudflare.com/cloudflare-one/connections/connect-apps/install-and-setup/installation/"
    echo ""
    exit 1
fi

echo "✅ cloudflared is installed"
echo ""

# Start Cloudflare Tunnel
echo "🚀 Starting Cloudflare Tunnel..."
echo ""
echo "This will create a public URL that M-Pesa can reach without any interstitial page."
echo "The URL will be displayed below. Copy it and update your MPESA_CALLBACK_URL in .env"
echo ""
echo "Press Ctrl+C to stop the tunnel"
echo ""

# Start tunnel (this will show the URL)
cloudflared tunnel --url http://localhost:5000

