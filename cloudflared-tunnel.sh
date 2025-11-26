#!/bin/bash

# Cloudflare Tunnel - Simple Setup
# Uses system-installed cloudflared or downloads if needed

echo "🌐 Cloudflare Tunnel for M-Pesa Callbacks"
echo "=========================================="
echo ""

# Check for system-installed cloudflared first
if command -v cloudflared &> /dev/null; then
    CLOUDFLARED_CMD="cloudflared"
    echo "✅ Using system-installed cloudflared"
elif [ -f "/usr/local/bin/cloudflared" ] && [ -x "/usr/local/bin/cloudflared" ]; then
    CLOUDFLARED_CMD="/usr/local/bin/cloudflared"
    echo "✅ Using /usr/local/bin/cloudflared"
else
    # Fallback: use local binary
    SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
    CLOUDFLARED_BIN="$SCRIPT_DIR/cloudflared"
    
    if [ ! -f "$CLOUDFLARED_BIN" ]; then
        echo "❌ cloudflared not found"
        echo ""
        echo "Install it using:"
        echo "  wget https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64.deb"
        echo "  sudo dpkg -i cloudflared-linux-amd64.deb"
        echo ""
        exit 1
    fi
    
    CLOUDFLARED_CMD="$CLOUDFLARED_BIN"
    echo "✅ Using local cloudflared binary"
fi

# Check version
echo "Version:"
"$CLOUDFLARED_CMD" --version | head -1
echo ""

# Check if server is running
if ! curl -s http://localhost:5000 > /dev/null 2>&1; then
    echo "⚠️  Warning: Laravel server may not be running on port 5000"
    echo "   Start it in another terminal: ./serve-5000.sh"
    echo ""
    read -p "Press Enter to continue anyway..."
fi

echo "🚀 Starting Cloudflare Tunnel..."
echo ""
echo "📋 IMPORTANT:"
echo "   1. Wait for the URL below (e.g., https://xxxxx.trycloudflare.com)"
echo "   2. Copy that URL"
echo "   3. Update .env file:"
echo "      MPESA_CALLBACK_URL=https://xxxxx.trycloudflare.com/api/payments/mpesa/callback"
echo "   4. Run: php artisan config:clear"
echo ""
echo "Press Ctrl+C to stop"
echo "----------------------------------------"
echo ""

# Start tunnel
"$CLOUDFLARED_CMD" tunnel --url http://localhost:5000

