#!/bin/bash

# Suppress Chrome sandbox warnings
export CHROME_NO_SANDBOX=1

# Start Laravel server on port 5000
echo "🚀 Starting LegitBooks server on port 5000..."
echo "📍 Server will be available at: http://127.0.0.1:5000"
echo ""
echo "Press Ctrl+C to stop the server"
echo ""

# Filter out sandbox warning messages
php artisan serve --host=127.0.0.1 --port=5000 2>&1 | grep -vE "(setuid sandbox|Failed to move to new namespace|Check failed|PID namespaces|prctl|Operation not permitted)" || true

