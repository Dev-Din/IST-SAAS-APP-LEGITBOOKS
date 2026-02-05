#!/bin/bash

# Start Cloudflare Tunnel for M-Pesa callback testing
# Forwards to your Laravel server port (8000 by default).

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# Configuration (must match your Laravel server port; serve.sh uses 8000)
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

if [ -z "$CLOUDFLARED_CMD" ]; then
    echo "Error: cloudflared is not installed."
    echo "Run: ./install-cloudflared.sh"
    exit 1
fi

# Check if Laravel server is running
if ! curl -s "${TUNNEL_URL}" > /dev/null 2>&1; then
    echo "Warning: Laravel server doesn't appear to be running on port ${LOCAL_PORT}"
    echo "Start it first: ./serve.sh  (or: php artisan serve --host=127.0.0.1 --port=${LOCAL_PORT})"
    echo ""
    echo "Press Ctrl+C to cancel, or wait 5 seconds to continue anyway..."
    sleep 5
fi

echo "Starting Cloudflare Tunnel..."
echo "Local URL: ${TUNNEL_URL}"
echo ""
echo "The tunnel URL will be shown below. Copy it and set in .env:"
echo "  MPESA_CALLBACK_BASE=https://<tunnel-url>"
echo "  MPESA_CALLBACK_URL=https://<tunnel-url>/api/payments/mpesa/callback"
echo ""
echo "Press Ctrl+C to stop the tunnel"
echo ""

# Start tunnel
"$CLOUDFLARED_CMD" tunnel --url "${TUNNEL_URL}"
