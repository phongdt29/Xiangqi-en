# Complete Production Deployment Guide

## 🚀 Cách Deploy Nhanh Nhất

**Local:** Build everything  
**Production:** Just extract & run

---

## **Step 1: Build Locally (Máy Của Bạn)**

```bash
cd /c/xampp/htdocs/Xiangqi-en

# Run build script (builds backend + frontend hoàn chỉnh)
chmod +x build-production.sh
./build-production.sh
```

**Script sẽ:**
- ✅ Clean backend vendor
- ✅ Cài composer dependencies (production only)
- ✅ Generate autoload
- ✅ Clean frontend build
- ✅ Cài npm dependencies
- ✅ Build Next.js (.next directory)
- ✅ Create zip packages (backend + frontend + vendor + .next)
- ✅ Create deployment scripts

**Output:**
```
production-build/
├── xiangqi-backend-20260817-133000.zip    (~50 MB)
├── xiangqi-frontend-20260817-133000.zip   (~30 MB)
├── DEPLOY.sh                               (Extract script)
├── RUN.sh                                  (Start script)
├── docker-compose.yml
├── .env.example
└── *.md                                    (Guides)
```

---

## **Step 2: Upload to Production**

```bash
# Upload ALL files from production-build/ to server
scp -r production-build/* user@server:/var/www/xiangqi-en/

# Or one by one
scp production-build/xiangqi-backend-*.zip user@server:/var/www/xiangqi-en/
scp production-build/xiangqi-frontend-*.zip user@server:/var/www/xiangqi-en/
scp production-build/*.sh user@server:/var/www/xiangqi-en/
```

---

## **Step 3: Deploy on Production Server**

```bash
# SSH vào server
ssh user@server

# Vào thư mục
cd /var/www/xiangqi-en

# Extract packages & configure
./DEPLOY.sh

# Edit configuration
nano .env
nano backend/.env.production

# Start deployment
./RUN.sh
```

**Xong! ✓**

---

## **What's Included in Packages**

### Backend Package (~50 MB)
```
✅ Source code
✅ vendor/ (composer dependencies, production optimized)
✅ Dockerfile
✅ nginx config
✅ supervisord config
✅ All configs
❌ .env files (you create these)
❌ Tests
❌ Dev dependencies
```

### Frontend Package (~30 MB)
```
✅ Source code
✅ .next/ (production build)
✅ public/
✅ Dockerfile
✅ All configs
❌ node_modules (not needed)
❌ .env files (you create these)
```

---

## **Configuration (One-Time)**

After extracting, edit 2 files:

### 1. `.env` (Root)
```env
LETSENCRYPT_EMAIL=your@email.com
DB_ROOT_PASSWORD=strong-password-here
DB_DATABASE=xiangqi
DB_USERNAME=xiangqi
DB_PASSWORD=strong-password-here
REVERB_APP_KEY=generate-random-key
```

### 2. `backend/.env.production`
```env
APP_NAME="Xiangqi Online"
APP_ENV=production
APP_KEY=base64:your-key-here
APP_DEBUG=false
APP_URL=https://api.chinesechess.online

# Database
DB_HOST=mysql
DB_DATABASE=xiangqi
DB_USERNAME=xiangqi
DB_PASSWORD=same-as-above

# CORS
CORS_ALLOWED_ORIGINS=https://chinesechess.online,https://www.chinesechess.online

# Reverb
REVERB_APP_ID=generate-id
REVERB_APP_KEY=same-as-root-env
REVERB_APP_SECRET=generate-secret
REVERB_HOST=ws.chinesechess.online
REVERB_PORT=443
REVERB_SCHEME=https

# PayPal (if using)
PAYPAL_MODE=live
PAYPAL_CLIENT_ID=your-live-id
PAYPAL_CLIENT_SECRET=your-live-secret
```

---

## **Deployment Timeline**

```
Local Build:           ~5-10 minutes
  - composer install   (~3 min)
  - npm install        (~2 min)
  - npm run build      (~1 min)
  - Create zip files   (~1 min)

Upload to Server:      ~2-5 minutes
  (depends on internet speed)

Production Deploy:     ~2 minutes
  - Extract            (~1 min)
  - docker build       (~1 min, no install needed!)
  - Start containers   (~10 sec)
  - Migrations         (~30 sec)

Total:                 ~10-17 minutes
```

---

## **What's Different From Manual Build**

| Step | Before | Now |
|------|--------|-----|
| On Local | Clone + build | Run `build-production.sh` |
| Upload to Server | Source only | Ready-to-run packages |
| On Server | `composer install` (5 min) | Extract zip |
| On Server | `npm install` (2 min) | Extract zip |
| On Server | `npm run build` (1 min) | Extract zip |
| On Server | Docker build | Fast build (no installs!) |
| **Total Time** | **15-20 min** | **~2 min on server** |

---

## **Verify Installation**

```bash
# Check containers
docker-compose ps

# Should show:
# - xiangqi-en-backend-1   (running)
# - xiangqi-en-mysql-1     (running)
# - xiangqi-en-frontend-1  (running) [if using docker frontend]
# - proxy                  (running)
# - acme                   (running)

# Test API
curl https://api.chinesechess.online/api/leaderboard

# Check logs
docker-compose logs backend | tail -20
docker-compose logs mysql | tail -20

# Check database
docker-compose exec backend php artisan tinker
>>> User::count()  # Should be 10
>>> Puzzle::count() # Should be 4
```

---

## **Troubleshooting**

### "Docker build failed"
```bash
# Check if vendor/ is in the zip
unzip -l xiangqi-backend-*.zip | grep vendor

# If vendor missing, run build-production.sh again locally
```

### "vendor/autoload.php not found"
```bash
# Means vendor/ not included
# Re-run build-production.sh on local machine
```

### "port 80 already in use"
```bash
# Kill existing process
docker-compose down
docker-compose up -d
```

### ".next not found"
```bash
# Check if frontend built correctly
unzip -l xiangqi-frontend-*.zip | grep ".next"

# Re-run build-production.sh
```

---

## **Security Checklist**

- ✅ Don't commit .env files
- ✅ Use strong passwords
- ✅ Keep REVERB keys secret
- ✅ Use HTTPS (auto with Let's Encrypt)
- ✅ Database credentials only in .env
- ✅ APP_DEBUG=false in production
- ✅ APP_ENV=production

---

## **Backup & Restore**

### Backup database
```bash
docker-compose exec -T mysql mysqldump -u xiangqi -p"password" xiangqi > backup-$(date +%Y%m%d).sql
```

### Restore database
```bash
docker-compose exec -T mysql mysql -u xiangqi -p"password" xiangqi < backup-20260817.sql
```

---

## **Update Code Later**

```bash
# Pull latest code
git pull origin main

# Rebuild locally
./build-production.sh

# Upload new packages
scp production-build/xiangqi-*.zip user@server:/var/www/xiangqi-en/

# On server:
docker-compose down
rm -rf backend frontend
./DEPLOY.sh  # Extract new packages
./RUN.sh     # Start
```

---

## **🎯 Summary**

```bash
# Local (1 command)
./build-production.sh

# Server (3 commands)
./DEPLOY.sh
# Edit .env files
./RUN.sh

# Done! No composer, no npm, no build on production!
```

---

**Ready for instant production deployment! ⚡**
