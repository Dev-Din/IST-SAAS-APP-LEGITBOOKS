#!/bin/bash

# Start Cloudflare Tunnel and show the URL clearly
# Usage: ./start-cloudflare-and-show-url.sh [port]
# Default port: 5000

set -e

LOCAL_PORT=${1:-5000}
TUNNEL_URL="http://127.0.0.1:${LOCAL_PORT}"

echo "=========================================="
echo "  Starting Cloudflare Tunnel"
echo "=========================================="
echo ""
echo "Local URL: ${TUNNEL_URL}"
echo ""
echo "⚠️  IMPORTANT: Look for the line that says:"
echo "   'https://xxxxx.trycloudflare.com'"
echo ""
echo "Copy that URL and update your .env file:"
echo "   MPESA_CALLBACK_BASE=https://xxxxx.trycloudflare.com"
echo ""
echo "Then run: php artisan config:clear"
echo ""
echo "=========================================="
echo ""

# Start tunnel (this will show the URL)
cloudflared tunnel --url "${TUNNEL_URL}"

