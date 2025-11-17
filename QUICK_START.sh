#!/bin/bash

# LegitBooks Quick Start Script

echo "🚀 Starting LegitBooks Setup..."

# Check if .env exists
if [ ! -f .env ]; then
    echo "❌ .env file not found. Please copy .env.example to .env and configure it."
    exit 1
fi

# Install dependencies if needed
if [ ! -d "vendor" ]; then
    echo "📦 Installing PHP dependencies..."
    composer install
fi

if [ ! -d "node_modules" ]; then
    echo "📦 Installing Node.js dependencies..."
    npm install
fi

# Generate key if not set
if ! grep -q "APP_KEY=base64:" .env; then
    echo "🔑 Generating application key..."
    php artisan key:generate
fi

# Run migrations
echo "🗄️  Running database migrations..."
php artisan migrate --force

# Seed database
echo "🌱 Seeding database..."
php artisan db:seed

# Build assets
echo "🎨 Building frontend assets..."
npm run build

echo ""
echo "✅ Setup complete!"
echo ""
echo "📝 Next steps:"
echo "   1. Start the server: php artisan serve"
echo "   2. Access admin panel: http://localhost:8000/admin"
echo "   3. Login: admin@legitbooks.com / password"
echo ""
echo "   Or use: composer run dev (includes queue, logs, and vite)"

