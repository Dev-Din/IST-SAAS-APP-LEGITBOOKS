#!/bin/bash

# Install Cloudflare Tunnel (cloudflared) for local development
# This script installs cloudflared on Linux systems

set -e

echo "Installing Cloudflare Tunnel (cloudflared)..."

# Detect OS
if [[ "$OSTYPE" == "linux-gnu"* ]]; then
    # Check if cloudflared is already installed
    if command -v cloudflared &> /dev/null; then
        echo "cloudflared is already installed: $(cloudflared --version)"
        exit 0
    fi

    # Download and install cloudflared
    ARCH=$(uname -m)
    if [ "$ARCH" = "x86_64" ]; then
        ARCH="amd64"
    elif [ "$ARCH" = "aarch64" ]; then
        ARCH="arm64"
    else
        echo "Unsupported architecture: $ARCH"
        exit 1
    fi

    echo "Downloading cloudflared for $ARCH..."
    wget -q https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-${ARCH} -O /tmp/cloudflared
    chmod +x /tmp/cloudflared
    sudo mv /tmp/cloudflared /usr/local/bin/cloudflared

    echo "cloudflared installed successfully!"
    cloudflared --version
else
    echo "This script is for Linux. For other OS, please install cloudflared manually:"
    echo "https://developers.cloudflare.com/cloudflare-one/connections/connect-apps/install-and-setup/installation/"
    exit 1
fi

