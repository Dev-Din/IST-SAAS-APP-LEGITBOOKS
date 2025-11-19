#!/bin/bash

# Suppress Chrome sandbox warnings (if Chrome/Chromium is used)
# Only set if Chrome sandbox actually exists
if [ -f "/usr/lib/chromium-browser/chrome-sandbox" ]; then
    export CHROME_DEVEL_SANDBOX=/usr/lib/chromium-browser/chrome-sandbox
fi

# Suppress any sandbox-related stderr messages from child processes
# Note: DOMPDF doesn't use Chrome, but other packages might
export CHROME_NO_SANDBOX=1 2>/dev/null || true

# Start Laravel server
echo "🚀 Starting LegitBooks server..."
echo "📍 Server will be available at: http://127.0.0.1:8000"
echo ""
echo "Admin Panel: http://127.0.0.1:8000/admin"
echo "Login: admin@legitbooks.com / password"
echo ""
echo "Press Ctrl+C to stop the server"
echo ""

# Filter out common sandbox warning messages
php artisan serve --host=127.0.0.1 --port=8000 2>&1 | grep -vE "(setuid sandbox|Failed to move to new namespace|Check failed|PID namespaces|prctl|Operation not permitted)" || true

