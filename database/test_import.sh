#!/bin/bash

# SQL Import Test Script
# This script tests importing setup.sql into a fresh MySQL database

set -e

DB_NAME="legitbooks_test"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-}"

echo "=========================================="
echo "LegitBooks SQL Import Test"
echo "=========================================="
echo ""

# Check if MySQL is available
if ! command -v mysql &> /dev/null; then
    echo "❌ Error: MySQL client not found."
    echo "Please install MySQL client tools."
    exit 1
fi

# Check if SQL file exists
SQL_FILE="database/setup.sql"
if [ ! -f "$SQL_FILE" ]; then
    echo "❌ Error: $SQL_FILE not found."
    exit 1
fi

echo "Database: $DB_NAME"
echo "User: $DB_USER"
echo "SQL File: $SQL_FILE"
echo ""

# Prompt for password if not set
if [ -z "$DB_PASS" ]; then
    read -sp "MySQL password for $DB_USER: " DB_PASS
    echo ""
fi

# Create database
echo "Creating database..."
mysql -u "$DB_USER" -p"$DB_PASS" -e "DROP DATABASE IF EXISTS $DB_NAME; CREATE DATABASE $DB_NAME;" || {
    echo "❌ Error: Failed to create database."
    exit 1
}

# Import SQL file
echo "Importing SQL file..."
mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$SQL_FILE" || {
    echo "❌ Error: Failed to import SQL file."
    exit 1
}

# Verify tables
echo ""
echo "Verifying tables..."
TABLE_COUNT=$(mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SHOW TABLES;" | wc -l)
TABLE_COUNT=$((TABLE_COUNT - 1))  # Subtract header row

echo "Found $TABLE_COUNT tables"

# Check for seed data
echo ""
echo "Verifying seed data..."
ADMIN_COUNT=$(mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SELECT COUNT(*) FROM admins;" -N)
ROLE_COUNT=$(mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SELECT COUNT(*) FROM roles WHERE guard_name = 'admin';" -N)
SETTINGS_COUNT=$(mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SELECT COUNT(*) FROM platform_settings;" -N)

echo "Admins: $ADMIN_COUNT"
echo "Roles: $ROLE_COUNT"
echo "Platform Settings: $SETTINGS_COUNT"

# Check foreign keys
echo ""
echo "Verifying foreign key constraints..."
FK_COUNT=$(mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = '$DB_NAME' AND CONSTRAINT_NAME != 'PRIMARY' AND REFERENCED_TABLE_NAME IS NOT NULL;" -N)
echo "Foreign key constraints: $FK_COUNT"

echo ""
echo "=========================================="
if [ "$TABLE_COUNT" -ge 40 ] && [ "$ADMIN_COUNT" -ge 1 ] && [ "$ROLE_COUNT" -ge 2 ]; then
    echo "✅ SQL import test PASSED"
    echo ""
    echo "Database is ready for use!"
    echo "Default admin: admin@legitbooks.com / password"
else
    echo "⚠️  SQL import completed but verification shows issues"
    echo "Expected: ≥40 tables, ≥1 admin, ≥2 roles"
    echo "Found: $TABLE_COUNT tables, $ADMIN_COUNT admin(s), $ROLE_COUNT role(s)"
fi
echo "=========================================="
