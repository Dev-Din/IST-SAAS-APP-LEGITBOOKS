#!/bin/bash

# Setup Cloudflare Tunnel for M-Pesa Callbacks (Free, No Interstitial)
# Cloudflare Tunnel provides a reliable way to expose local server for M-Pesa callbacks
# Use the same port as your Laravel server (serve.sh uses 8000 by default).

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

LOCAL_PORT=${LOCAL_PORT:-8000}
TUNNEL_URL="http://127.0.0.1:${LOCAL_PORT}"

# Prefer local binary (from install-cloudflared.sh on Windows)
if [ -f "./cloudflared.exe" ]; then
    CLOUDFLARED_CMD="./cloudflared.exe"
elif [ -x "./cloudflared" ]; then
    CLOUDFLARED_CMD="./cloudflared"
elif command -v cloudflared &> /dev/null; then
    CLOUDFLARED_CMD="cloudflared"
else
    CLOUDFLARED_CMD=""
fi

echo "🌐 Setting up Cloudflare Tunnel for M-Pesa Callbacks"
echo "===================================================="
echo ""

if [ -z "$CLOUDFLARED_CMD" ]; then
    echo "❌ cloudflared is not installed"
    echo ""
    echo "Run: ./install-cloudflared.sh"
    echo "  (On Windows this downloads cloudflared into this folder.)"
    echo ""
    echo "Or install manually:"
    echo "  Windows: winget install Cloudflare.cloudflared"
    echo "  https://developers.cloudflare.com/cloudflare-one/connections/connect-apps/install-and-setup/installation/"
    echo ""
    exit 1
fi

echo "✅ cloudflared is available"
echo ""

# Check if Laravel server is running
if ! curl -s "${TUNNEL_URL}" > /dev/null 2>&1; then
    echo "⚠️  Laravel server doesn't appear to be running on port ${LOCAL_PORT}"
    echo "   Start it first in another terminal: ./serve.sh"
    echo ""
    read -p "Continue anyway? (y/N) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

echo "🚀 Starting Cloudflare Tunnel..."
echo ""
echo "1. Keep this terminal open. Cloudflare will print a URL like:"
echo "   https://xxxxx-xx-xx-xx-xx.trycloudflare.com"
echo ""
echo "2. Copy that URL (without a trailing slash). Then in your .env set:"
echo "   MPESA_CALLBACK_BASE=https://xxxxx-xx-xx-xx-xx.trycloudflare.com"
echo "   MPESA_CALLBACK_URL=https://xxxxx-xx-xx-xx-xx.trycloudflare.com/api/payments/mpesa/callback"
echo ""
echo "3. Restart your Laravel app (or run: php artisan config:clear) so it picks up the new URL."
echo ""
echo "4. M-Pesa will send callbacks to that URL when the customer completes the STK push."
echo ""
echo "Press Ctrl+C to stop the tunnel"
echo ""

# Start tunnel (this will show the URL)
"$CLOUDFLARED_CMD" tunnel --url "${TUNNEL_URL}"

