# LegitBooks - Quick Start Guide

## Prerequisites

Make sure you have:
- PHP 8.2 or higher
- Composer installed
- MySQL/MariaDB running
- Node.js and npm installed

## Step-by-Step Setup

### 1. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install
```

### 2. Configure Environment

The `.env` file already exists. Make sure it has the following settings:

```env
APP_NAME=LegitBooks
APP_ENV=local
APP_KEY=  # Run: php artisan key:generate
APP_DEBUG=true
APP_TIMEZONE=UTC
APP_URL=http://localhost:8000

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=legitbooks
DB_USERNAME=root
DB_PASSWORD=your_password

# Branding Configuration
BRANDING_MODE=A
LEGITBOOKS_NAME=LegitBooks

# Mail Configuration (Mailgun)
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=your-domain.mailgun.org
MAILGUN_SECRET=your-mailgun-secret
MAILGUN_ENDPOINT=api.mailgun.net

# M-Pesa Configuration (Sandbox)
MPESA_CONSUMER_KEY=your-consumer-key
MPESA_CONSUMER_SECRET=your-consumer-secret
MPESA_SHORTCODE=your-shortcode
MPESA_PASSKEY=your-passkey
MPESA_ENVIRONMENT=sandbox
```

### 3. Generate Application Key

```bash
php artisan key:generate
```

### 4. Create Database

Create a MySQL database:

```bash
mysql -u root -p
CREATE DATABASE legitbooks;
exit;
```

Or use your preferred database management tool.

### 5. Run Migrations

```bash
php artisan migrate
```

This will create all the necessary tables for:
- Platform (admins, tenants, subscriptions, etc.)
- Tenant-scoped (users, invoices, payments, etc.)

### 6. Seed Database

```bash
php artisan db:seed
```

This will create:
- A super admin user (admin@legitbooks.com / password: `password`)
- A demo tenant with demo data

### 7. Build Frontend Assets

```bash
npm run build
```

Or for development with hot reload:
```bash
npm run dev
```

### 8. Start the Development Server

**Option 1: Laravel Development Server**
```bash
php artisan serve
```
The app will be available at: http://localhost:8000

**Option 2: Using the dev script (includes queue, logs, and vite)**
```bash
composer run dev
```

## Accessing the Application

### Admin Panel
- URL: http://localhost:8000/admin
- Login: admin@legitbooks.com
- Password: password

### Demo Tenant
After seeding, you can access the demo tenant:
1. Get the tenant hash from the database or seeder output
2. Access: http://localhost:8000/app/{tenant_hash}/dashboard
3. Login: admin@demo.com or user@demo.com
4. Password: password

### Create a New Tenant

**Via Artisan Command:**
```bash
php artisan legitbooks:tenant:create "My Company" company@example.com --seed-demo
```

**Via Admin Panel:**
1. Login to admin panel
2. Navigate to Tenants
3. Click "Create New Tenant"

## Common Commands

### M-Pesa Simulation
```bash
php artisan mpesa:simulate {tenant_hash} 254712345678 1000
```

### Tenant Backup
```bash
php artisan tenant:backup {tenant_hash}
```

### Clear Cache
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## Troubleshooting

### Migration Errors
If you encounter migration errors:
```bash
php artisan migrate:fresh --seed
```
⚠️ **Warning**: This will drop all tables and recreate them!

### Permission Issues
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Database Connection Issues
- Verify MySQL is running
- Check database credentials in `.env`
- Ensure database exists

### Asset Build Issues
```bash
rm -rf node_modules
npm install
npm run build
```

## Next Steps

1. **Configure Mailgun** for email sending
2. **Set up M-Pesa** sandbox credentials
3. **Customize branding** via admin panel
4. **Create your first tenant** and start using the system

## Development Tips

- Use `php artisan tinker` to interact with the database
- Check logs in `storage/logs/laravel.log`
- Use `php artisan route:list` to see all routes
- Use `php artisan migrate:status` to check migration status

