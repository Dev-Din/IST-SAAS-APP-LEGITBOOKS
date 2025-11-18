#!/bin/bash

# Suppress Chrome sandbox warnings
export CHROME_DEVEL_SANDBOX=/usr/lib/chromium-browser/chrome-sandbox

# Start Laravel server
echo "🚀 Starting LegitBooks server..."
echo "📍 Server will be available at: http://127.0.0.1:8000"
echo ""
echo "Admin Panel: http://127.0.0.1:8000/admin"
echo "Login: admin@legitbooks.com / password"
echo ""
echo "Press Ctrl+C to stop the server"
echo ""

php artisan serve --host=127.0.0.1 --port=8000 2>&1 | grep -v "setuid sandbox" | grep -v "Failed to move to new namespace" | grep -v "Check failed"

