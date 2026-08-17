#!/bin/bash

# Complete production build - Local only
# This creates ready-to-deploy packages with everything included
# No build needed on production server!

set -e

echo "=========================================="
echo "Complete Production Build (Local)"
echo "=========================================="
echo ""

PROJECT_DIR="$(pwd)"
BUILD_DIR="$PROJECT_DIR/production-build"
TIMESTAMP=$(date +%Y%m%d-%H%M%S)

# Step 1: Clean backend vendor
echo "Step 1: Clean and rebuild backend vendor..."
cd "$PROJECT_DIR/backend"

# Remove old vendor
rm -rf vendor

# Install dependencies (production only, no dev)
echo "  Installing composer dependencies..."
composer install --no-dev --optimize-autoloader --no-scripts

# Generate autoload
echo "  Generating autoload..."
composer dump-autoload --no-dev --optimize

echo "  ✓ Vendor ready"

# Step 2: Build frontend
echo ""
echo "Step 2: Build frontend..."
cd "$PROJECT_DIR/frontend"

# Clean old build
rm -rf .next out node_modules/.cache

# Install dependencies
echo "  Installing npm dependencies..."
npm ci

# Build production
echo "  Building Next.js..."
npm run build

echo "  ✓ Frontend built (with .next directory)"

# Step 3: Create production packages
echo ""
echo "Step 3: Creating production packages..."

mkdir -p "$BUILD_DIR"
cd "$BUILD_DIR"

# Package backend WITH vendor
echo "  Creating backend package (with vendor)..."
zip -r -q "xiangqi-backend-$TIMESTAMP.zip" \
  "$PROJECT_DIR/backend" \
  -x "$PROJECT_DIR/backend/.env*" \
  "$PROJECT_DIR/backend/.git*" \
  "$PROJECT_DIR/backend/docker/*" \
  "$PROJECT_DIR/backend/tests/*" \
  "$PROJECT_DIR/backend/storage/logs/*" \
  "$PROJECT_DIR/backend/bootstrap/cache/*" \
  "$PROJECT_DIR/backend/.phpunit*"

BACKEND_SIZE=$(du -h "xiangqi-backend-$TIMESTAMP.zip" | cut -f1)
echo "    ✓ Backend package: $BACKEND_SIZE"

# Package frontend WITH .next build
echo "  Creating frontend package (with build)..."
zip -r -q "xiangqi-frontend-$TIMESTAMP.zip" \
  "$PROJECT_DIR/frontend" \
  -x "$PROJECT_DIR/frontend/node_modules/*" \
  "$PROJECT_DIR/frontend/.git*" \
  "$PROJECT_DIR/frontend/.env*"

FRONTEND_SIZE=$(du -h "xiangqi-frontend-$TIMESTAMP.zip" | cut -f1)
echo "    ✓ Frontend package: $FRONTEND_SIZE"

# Step 4: Copy deployment files
echo ""
echo "Step 4: Copying deployment files..."
cp "$PROJECT_DIR/.env.example" "$BUILD_DIR/"
cp "$PROJECT_DIR/backend/.env.production.example" "$BUILD_DIR/.env.production.example"
cp "$PROJECT_DIR/backend/.env.example" "$BUILD_DIR/backend.env.example"
cp "$PROJECT_DIR/docker-compose.yml" "$BUILD_DIR/"
cp "$PROJECT_DIR/deploy/DEPLOYMENT.md" "$BUILD_DIR/"
cp "$PROJECT_DIR/deploy/CORS-FIX.md" "$BUILD_DIR/"

echo "  ✓ Deployment files copied"

# Step 5: Create deployment script
echo ""
echo "Step 5: Creating deployment script..."
cat > "$BUILD_DIR/DEPLOY.sh" << 'EOF'
#!/bin/bash

# Simple deployment script
# Just extract and run!

set -e

echo "Deploying Xiangqi to production..."
echo ""

# Extract packages
echo "Step 1: Extracting packages..."
unzip -q xiangqi-backend-*.zip
unzip -q xiangqi-frontend-*.zip
echo "  ✓ Extracted"

# Configure environment
echo ""
echo "Step 2: Configuring environment..."
[ -f .env ] || cp .env.example .env
[ -f backend/.env.production ] || cp .env.production.example backend/.env.production
echo "  ✓ Environment files created"
echo "  IMPORTANT: Edit .env and backend/.env.production with your values!"
echo "  Then run: ./RUN.sh"

EOF

chmod +x "$BUILD_DIR/DEPLOY.sh"

# Create run script
cat > "$BUILD_DIR/RUN.sh" << 'EOF'
#!/bin/bash

# Run production deployment

set -e

echo "Starting Xiangqi production deployment..."
echo ""

# Verify configuration
if [ ! -f .env ] || [ ! -f backend/.env.production ]; then
    echo "ERROR: Configuration files not found!"
    echo "Run ./DEPLOY.sh first to extract and configure"
    exit 1
fi

echo "Step 1: Building Docker images (with pre-built vendor)..."
docker-compose build --no-cache backend frontend

echo ""
echo "Step 2: Starting containers..."
docker-compose up -d

echo ""
echo "Step 3: Waiting for database..."
sleep 10

echo ""
echo "Step 4: Running migrations..."
docker-compose exec backend php artisan migrate --force

echo ""
echo "Step 5: Seeding database..."
docker-compose exec backend php artisan db:seed

echo ""
echo "✓ =========================================="
echo "✓ Deployment Complete!"
echo "✓ =========================================="
echo ""
echo "Access your site:"
echo "  Frontend: https://chinesechess.online"
echo "  API: https://api.chinesechess.online"
echo ""
echo "View logs:"
echo "  docker-compose logs -f"

EOF

chmod +x "$BUILD_DIR/RUN.sh"

echo "  ✓ Deployment scripts created"

# Step 6: Summary
echo ""
echo "=========================================="
echo "✓ Production Build Complete!"
echo "=========================================="
echo ""
echo "Output directory: $BUILD_DIR"
echo ""
echo "Files ready to deploy:"
ls -lh "$BUILD_DIR" | grep -E "zip|yml|md|sh" | awk '{print "  " $9 " (" $5 ")"}'
echo ""
echo "Next steps:"
echo "  1. Upload all files to production server"
echo "  2. SSH into server"
echo "  3. Run: ./DEPLOY.sh"
echo "  4. Edit .env and backend/.env.production"
echo "  5. Run: ./RUN.sh"
echo ""
echo "That's it! No composer, no npm needed on production!"
echo ""
