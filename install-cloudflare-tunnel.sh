#!/bin/bash

echo "Installing Cloudflare Tunnel..."
echo "=============================="
echo ""

# Check if already installed
if command -v cloudflared &> /dev/null; then
    echo "✅ cloudflared is already installed"
    cloudflared --version
    exit 0
fi

# Detect OS and install
if [[ "$OSTYPE" == "linux-gnu"* ]]; then
    echo "Detected Linux. Installing..."
    
    # Download
    ARCH=$(uname -m)
    if [ "$ARCH" == "x86_64" ]; then
        URL="https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64"
    elif [ "$ARCH" == "aarch64" ]; then
        URL="https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-arm64"
    else
        echo "❌ Unsupported architecture: $ARCH"
        exit 1
    fi
    
    echo "Downloading cloudflared..."
    wget -q "$URL" -O /tmp/cloudflared
    
    if [ $? -eq 0 ]; then
        chmod +x /tmp/cloudflared
        
        # Try to install without sudo first
        if mv /tmp/cloudflared ~/.local/bin/cloudflared 2>/dev/null; then
            echo "✅ Installed to ~/.local/bin/cloudflared"
            echo "Add to PATH: export PATH=\$PATH:~/.local/bin"
        elif sudo mv /tmp/cloudflared /usr/local/bin/cloudflared 2>/dev/null; then
            echo "✅ Installed to /usr/local/bin/cloudflared"
        else
            echo "❌ Installation failed. Please run manually:"
            echo "   sudo mv /tmp/cloudflared /usr/local/bin/cloudflared"
            exit 1
        fi
        
        echo ""
        echo "✅ Installation complete!"
        cloudflared --version
    else
        echo "❌ Download failed"
        exit 1
    fi
    
elif [[ "$OSTYPE" == "darwin"* ]]; then
    echo "Detected macOS. Installing via Homebrew..."
    if command -v brew &> /dev/null; then
        brew install cloudflared
    else
        echo "❌ Homebrew not found. Install manually from:"
        echo "   https://developers.cloudflare.com/cloudflare-one/connections/connect-apps/install-and-setup/installation/"
    fi
else
    echo "❌ Unsupported OS. Install manually from:"
    echo "   https://developers.cloudflare.com/cloudflare-one/connections/connect-apps/install-and-setup/installation/"
    exit 1
fi

