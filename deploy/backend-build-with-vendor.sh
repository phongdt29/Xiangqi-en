#!/bin/bash

# Build backend package WITH vendor directory
# Usage: ./backend-build-with-vendor.sh

set -e

echo "Building backend package with vendor directory..."
echo ""

PROJECT_DIR="/c/xampp/htdocs/Xiangqi-en"
TEMP_DIR=$(mktemp -d)
OUTPUT_FILE="backend-deploy-with-vendor.zip"

# Copy backend to temp directory
echo "Step 1: Copying backend files..."
cp -r "$PROJECT_DIR/backend" "$TEMP_DIR/backend"

# Ensure vendor is present
echo "Step 2: Checking vendor directory..."
if [ ! -d "$TEMP_DIR/backend/vendor" ]; then
    echo "ERROR: vendor directory not found!"
    echo "Run 'composer install' in backend/ first"
    exit 1
fi

# Remove unnecessary files to reduce size
echo "Step 3: Cleaning up unnecessary files..."
cd "$TEMP_DIR/backend"

# Remove development files
rm -rf vendor/*/tests
rm -rf vendor/*/test
rm -rf vendor/*/.git
rm -rf vendor/*/.github
rm -rf .env
rm -rf .env.production
rm -rf .phpunit.result.cache
rm -rf storage/logs/*
rm -rf bootstrap/cache/*

# Create zip
echo "Step 4: Creating zip package..."
cd "$TEMP_DIR"
zip -r -q "$OUTPUT_FILE" backend/

# Move to deploy directory
mv "$OUTPUT_FILE" "$PROJECT_DIR/deploy/$OUTPUT_FILE"

# Cleanup
rm -rf "$TEMP_DIR"

FILE_SIZE=$(du -h "$PROJECT_DIR/deploy/$OUTPUT_FILE" | cut -f1)
echo ""
echo "✓ Backend package created with vendor!"
echo "File: deploy/$OUTPUT_FILE"
echo "Size: $FILE_SIZE"
echo ""
echo "Usage on production server:"
echo "  unzip backend-deploy-with-vendor.zip"
echo "  # No need to run 'composer install'"
echo "  docker-compose up -d --build"
