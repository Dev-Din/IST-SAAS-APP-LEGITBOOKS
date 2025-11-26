#!/bin/bash

# Alternative Cloudflare Tunnel - Use system cloudflared if available
# Or provide manual instructions

echo "🌐 Cloudflare Tunnel for M-Pesa Callbacks"
echo "=========================================="
echo ""

# Check if cloudflared is in PATH
if command -v cloudflared &> /dev/null; then
    echo "✅ Found cloudflared in system PATH"
    CLOUDFLARED_CMD="cloudflared"
elif [ -f "$HOME/.local/bin/cloudflared" ]; then
    echo "✅ Found cloudflared in ~/.local/bin"
    CLOUDFLARED_CMD="$HOME/.local/bin/cloudflared"
else
    echo "❌ cloudflared not found"
    echo ""
    echo "Install it using one of these methods:"
    echo ""
    echo "Option 1: Download manually"
    echo "  wget https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64"
    echo "  chmod +x cloudflared-linux-amd64"
    echo "  sudo mv cloudflared-linux-amd64 /usr/local/bin/cloudflared"
    echo ""
    echo "Option 2: Use snap (if available)"
    echo "  sudo snap install cloudflared"
    echo ""
    echo "Option 3: Use package manager"
    echo "  # Debian/Ubuntu:"
    echo "  wget -q https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64.deb"
    echo "  sudo dpkg -i cloudflared-linux-amd64.deb"
    echo ""
    exit 1
fi

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

