# Deploy Backend WITH Vendor Directory

## Why Include Vendor?

✅ **Advantages:**
- Deploy in seconds (no composer install needed)
- No composer required on production
- Guaranteed dependency versions
- Faster, more reliable deployment
- Works offline on production

---

## Step 1: Prepare Backend Locally

Make sure you have vendor directory built locally:

```bash
cd backend
composer install --no-dev --optimize-autoloader
```

Verify:
```bash
ls -la backend/vendor/  # Should have 300+ directories
```

---

## Step 2: Create Backend Package WITH Vendor

### Option A: Using Script

```bash
chmod +x deploy/backend-build-with-vendor.sh
./deploy/backend-build-with-vendor.sh
```

This creates: `backend-deploy-with-vendor.zip` (30-40 MB)

### Option B: Manual

```bash
cd deploy
zip -r backend-deploy-with-vendor.zip ../backend/ \
  -x "*/tests/*" "*/.git/*" "*/.github/*" \
  "../backend/.env*" "../backend/.phpunit*"
```

---

## Step 3: Upload to Production

```bash
# Copy to server
scp backend-deploy-with-vendor.zip user@server:/var/www/xiangqi-en/

# Or use git (faster)
git add backend/vendor
git commit -m "Add vendor directory for production"
git push origin main
```

---

## Step 4: Deploy on Production Server

```bash
cd /var/www/xiangqi-en

# Extract (vendor already included)
unzip backend-deploy-with-vendor.zip

# Configure
cp .env.example .env
cp backend/.env.production.example backend/.env.production
nano .env
nano backend/.env.production

# Build Docker image (will use pre-built vendor)
docker-compose build backend

# Start
docker-compose up -d

# Run migrations
docker-compose exec backend php artisan migrate --force
docker-compose exec backend php artisan db:seed
```

**Done! No composer install needed.** ⚡

---

## Production Dockerfile (Optimized)

Use this Dockerfile on production to skip composer:

```dockerfile
FROM php:8.4-fpm

# Install dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    nginx supervisor git unzip libzip-dev libpng-dev libonig-dev && \
    docker-php-ext-install pdo_mysql mysqli mbstring zip gd bcmath pcntl && \
    rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# Copy pre-built vendor (SKIP composer install!)
COPY vendor/ vendor/

# Copy application
COPY . .

# Prepare directories
RUN mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache && \
    chown -R www-data:www-data storage bootstrap/cache

# Copy configs
COPY docker/nginx.conf /etc/nginx/sites-enabled/default
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80 8080
ENTRYPOINT ["/entrypoint.sh"]
```

Save as: `Dockerfile.prod` in backend directory

Build:
```bash
docker build -f Dockerfile.prod -t xiangqi-backend:latest .
```

---

## Compare: With vs Without Vendor

### Without Vendor (Current)
```
Time:     5-10 minutes (composer install)
Size:     148 KB (source only)
Risk:     Composer version differences
Requires: composer on production
```

### With Vendor (This Guide)
```
Time:     30 seconds (extract & build)
Size:     30-40 MB (compressed)
Risk:     None (exact dependencies)
Requires: nothing
```

---

## File Size Breakdown

```
backend-deploy.zip                    = 148 KB
backend-deploy-with-vendor.zip        = 30-40 MB

Total with frontend                   = 30-40 MB + 113 MB = 143-153 MB
(Still reasonable for production)
```

---

## Automated Build Script

For CI/CD, use this:

```bash
#!/bin/bash

# Build backend with vendor
cd backend

# Ensure vendor is built
if [ ! -d "vendor" ]; then
    composer install --no-dev --optimize-autoloader
fi

# Create zip
cd ..
zip -r backend-deploy-with-vendor.zip backend/ \
  -x "*/tests/*" "*/.git/*" "backend/.env*"

# Verify size
du -h backend-deploy-with-vendor.zip

echo "✓ Backend package ready for production"
echo "  Includes: Source code + vendor + optimized"
echo "  Size: $(du -h backend-deploy-with-vendor.zip | cut -f1)"
```

---

## Security Notes

When including vendor:

- ✅ Already compiled/optimized
- ✅ Dev dependencies removed (`--no-dev`)
- ✅ Tests removed (unnecessary)
- ✅ .git directories removed
- ✅ .env files excluded
- ✅ Safe for production

---

## Git Strategy

**Option 1: Always include vendor**
```bash
# In .gitignore, remove vendor/
git add backend/vendor
git commit -m "Add vendor for production deployment"
```

**Option 2: Use git-lfs for large files**
```bash
git lfs install
git lfs track "backend/vendor/"
git add backend/vendor
git push origin main
```

**Option 3: Keep vendor separate**
```bash
# Maintain as before, build locally before deploying
./deploy/backend-build-with-vendor.sh
# scp to server
```

---

## Deployment Speed

### Before (without vendor)
```
1. Extract zip           = 2 sec
2. composer install      = 5-10 min
3. docker build          = 2 min
4. Start container       = 10 sec
Total:                     7-12 minutes
```

### After (with vendor)
```
1. Extract zip           = 2 sec
2. docker build          = 1 min
3. Start container       = 10 sec
Total:                     ~1.5 minutes
```

**10x faster! ⚡**

---

## Rollback if Needed

```bash
# Keep previous vendor backup
docker-compose exec backend tar czf /tmp/vendor-backup.tar.gz vendor/

# If something breaks
docker-compose down
git checkout previous-commit
docker-compose up -d --build
```

---

**Ready for lightning-fast production deployment!** 🚀
