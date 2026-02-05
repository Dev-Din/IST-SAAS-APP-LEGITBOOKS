#!/bin/bash

# Install Cloudflare Tunnel (cloudflared) for local development
# Supports Linux and Windows (Git Bash / MINGW64)

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

echo "Installing Cloudflare Tunnel (cloudflared)..."

# If already in PATH, done
if command -v cloudflared &> /dev/null; then
    echo "cloudflared is already installed: $(cloudflared --version)"
    exit 0
fi

# If local binary exists (project directory), done
if [ -f "./cloudflared.exe" ] && [ -x "./cloudflared.exe" ]; then
    echo "cloudflared already present in project: $(./cloudflared.exe --version)"
    exit 0
fi
if [ -f "./cloudflared" ] && [ -x "./cloudflared" ]; then
    echo "cloudflared already present in project: $(./cloudflared --version)"
    exit 0
fi

# Detect OS
if [[ "$OSTYPE" == "linux-gnu"* ]] || [[ "$OSTYPE" == "linux"* ]]; then
    ARCH=$(uname -m)
    if [ "$ARCH" = "x86_64" ]; then
        ARCH="amd64"
    elif [ "$ARCH" = "aarch64" ] || [ "$ARCH" = "arm64" ]; then
        ARCH="arm64"
    else
        echo "Unsupported architecture: $ARCH"
        exit 1
    fi

    echo "Downloading cloudflared for Linux $ARCH..."
    wget -q "https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-${ARCH}" -O /tmp/cloudflared
    chmod +x /tmp/cloudflared
    sudo mv /tmp/cloudflared /usr/local/bin/cloudflared
    echo "cloudflared installed successfully!"
    cloudflared --version

elif [[ "$OSTYPE" == "msys" ]] || [[ "$OSTYPE" == "cygwin" ]] || [[ "$OSTYPE" == "mingw"* ]] || [[ "$(uname -o 2>/dev/null)" == "Msys" ]]; then
    # Windows (Git Bash / MINGW64)
    ARCH="amd64"
    if [ "$(uname -m)" = "aarch64" ] || [ "$(uname -m)" = "arm64" ]; then
        ARCH="arm64"
    fi

    CLOUDFLARED_EXE="cloudflared-windows-${ARCH}.exe"
    DOWNLOAD_URL="https://github.com/cloudflare/cloudflared/releases/latest/download/${CLOUDFLARED_EXE}"

    echo "Downloading cloudflared for Windows ($ARCH)..."
    if command -v curl &> /dev/null; then
        curl -sL -o "cloudflared.exe" "$DOWNLOAD_URL"
    elif command -v wget &> /dev/null; then
        wget -q -O "cloudflared.exe" "$DOWNLOAD_URL"
    else
        echo "Need curl or wget. Install one, or download manually:"
        echo "  $DOWNLOAD_URL"
        echo "  Save as: $SCRIPT_DIR/cloudflared.exe"
        exit 1
    fi

    chmod +x cloudflared.exe 2>/dev/null || true
    echo "cloudflared installed to project directory: $SCRIPT_DIR/cloudflared.exe"
    ./cloudflared.exe --version

else
    echo "Unsupported OS: $OSTYPE"
    echo "Install cloudflared manually:"
    echo "  https://developers.cloudflare.com/cloudflare-one/connections/connect-apps/install-and-setup/installation/"
    echo "  Windows: winget install Cloudflare.cloudflared"
    echo "  Or download: https://github.com/cloudflare/cloudflared/releases"
    exit 1
fi

