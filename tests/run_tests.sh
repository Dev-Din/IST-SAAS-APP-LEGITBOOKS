#!/bin/bash

# Test Runner Script for LegitBooks
# This script runs the PHPUnit test suite with proper configuration

set -e

echo "=========================================="
echo "LegitBooks Test Suite Runner"
echo "=========================================="
echo ""

# Check if composer dependencies are installed
if [ ! -d "vendor" ]; then
    echo "❌ Error: Composer dependencies not installed."
    echo "Please run: composer install"
    exit 1
fi

# Check if PHPUnit is available
if [ ! -f "vendor/bin/phpunit" ]; then
    echo "❌ Error: PHPUnit not found."
    echo "Please run: composer install"
    exit 1
fi

echo "Running PHPUnit test suite..."
echo ""

# Run all tests
vendor/bin/phpunit

echo ""
echo "=========================================="
echo "Test execution complete!"
echo "=========================================="
