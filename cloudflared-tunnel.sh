#!/bin/bash

# Start Cloudflare Tunnel for M-Pesa callback testing
# This creates an ephemeral tunnel URL that forwards to localhost:5000

set -e

# Configuration
LOCAL_PORT=${LOCAL_PORT:-5000}
TUNNEL_URL="http://127.0.0.1:${LOCAL_PORT}"

# Check if cloudflared is installed
if ! command -v cloudflared &> /dev/null; then
    echo "Error: cloudflared is not installed."
    echo "Run: ./install-cloudflared.sh"
    exit 1
fi

# Check if Laravel server is running
if ! curl -s "http://127.0.0.1:${LOCAL_PORT}" > /dev/null 2>&1; then
    echo "Warning: Laravel server doesn't appear to be running on port ${LOCAL_PORT}"
    echo "Start it with: php artisan serve --host=127.0.0.1 --port=${LOCAL_PORT}"
    echo ""
    echo "Press Ctrl+C to cancel, or wait 5 seconds to continue anyway..."
    sleep 5
fi

echo "Starting Cloudflare Tunnel..."
echo "Local URL: ${TUNNEL_URL}"
echo ""
echo "The tunnel URL will be displayed below. Copy it and update your .env:"
echo "MPESA_CALLBACK_BASE=https://<tunnel-url>"
echo ""
echo "Press Ctrl+C to stop the tunnel"
echo ""

# Start tunnel
cloudflared tunnel --url "${TUNNEL_URL}"
