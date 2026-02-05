#!/bin/bash

# LegitBooks Development Server
# Uses PHP's built-in server directly to avoid Chrome sandbox errors

PORT=${1:-8000}
HOST=${2:-127.0.0.1}

echo "🚀 Starting LegitBooks development server..."
echo "   URL: http://${HOST}:${PORT}"
echo "   Admin: http://${HOST}:${PORT}/admin"
echo "   Tenant: http://${HOST}:${PORT}/app"
echo ""
echo "Press Ctrl+C to stop the server"
echo ""

php -S ${HOST}:${PORT} -t public

