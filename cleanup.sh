#!/bin/bash

# LegitBooks Cleanup Script
# This script removes cache files and build artifacts that can be safely regenerated

set -e

echo "🧹 LegitBooks Cleanup Script"
echo "=============================="
echo ""
echo "This script will remove cache files and build artifacts."
echo "These files can be regenerated and are safe to delete."
echo ""

# Confirm before proceeding
read -p "Do you want to continue? (y/N) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Cleanup cancelled."
    exit 1
fi

echo ""
echo "Clearing Laravel caches..."

# Clear Laravel caches
php artisan cache:clear 2>/dev/null || echo "⚠️  Could not clear cache"
php artisan config:clear 2>/dev/null || echo "⚠️  Could not clear config cache"
php artisan route:clear 2>/dev/null || echo "⚠️  Could not clear route cache"
php artisan view:clear 2>/dev/null || echo "⚠️  Could not clear view cache"

echo "✅ Laravel caches cleared"
echo ""

# Optional: Remove node_modules (uncomment if needed)
# echo "Removing node_modules..."
# rm -rf node_modules
# echo "✅ node_modules removed"
# echo ""

# Optional: Remove vendor (uncomment if needed - requires composer install after)
# echo "Removing vendor..."
# rm -rf vendor
# echo "✅ vendor removed"
# echo ""

# Remove compiled views
if [ -d "storage/framework/views" ]; then
    echo "Removing compiled views..."
    find storage/framework/views -type f -name "*.php" ! -name ".gitignore" -delete
    echo "✅ Compiled views removed"
    echo ""
fi

# Remove session files (keep directory structure)
if [ -d "storage/framework/sessions" ]; then
    echo "Removing session files..."
    find storage/framework/sessions -type f ! -name ".gitignore" -delete
    echo "✅ Session files removed"
    echo ""
fi

# Remove cache files (keep directory structure)
if [ -d "storage/framework/cache" ]; then
    echo "Removing cache files..."
    find storage/framework/cache/data -mindepth 1 -maxdepth 1 -type d -exec rm -rf {} + 2>/dev/null || true
    echo "✅ Cache files removed"
    echo ""
fi

# Remove log files (optional - uncomment if needed)
# if [ -d "storage/logs" ]; then
#     echo "Removing log files..."
#     find storage/logs -type f -name "*.log" -delete
#     echo "✅ Log files removed"
#     echo ""
# fi

echo "✨ Cleanup complete!"
echo ""
echo "Next steps:"
echo "  - Run 'composer install' if vendor was removed"
echo "  - Run 'npm install' if node_modules was removed"
echo "  - Run 'php artisan config:cache' for production"
echo "  - Run 'php artisan route:cache' for production"
echo "  - Run 'php artisan view:cache' for production"
echo ""

