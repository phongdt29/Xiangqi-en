# Xiangqi Online - Production Deployment Guide

## Overview
This guide explains how to deploy the Xiangqi Online application to a production server using the provided deployment packages.

## Prerequisites
- Docker and Docker Compose installed
- Git installed (for cloning or pulling updates)
- Access to production server (CyberPanel, VPS, or cloud hosting)

## Deployment Files

| File | Size | Purpose |
|------|------|---------|
| `backend-deploy.zip` | ~148 KB | Laravel backend source code with Dockerfile |
| `frontend-deploy.zip` | ~113 KB | Next.js frontend source code with Dockerfile |
| `xiangqi-production-import.sql` | ~19 KB | Database schema and initial data |

## Step-by-Step Deployment

### 1. Prepare the Server

```bash
# SSH into production server
ssh user@your-production-server

# Create deployment directory
mkdir -p /var/www/xiangqi-en
cd /var/www/xiangqi-en

# Extract deployment packages
unzip /path/to/backend-deploy.zip
unzip /path/to/frontend-deploy.zip
```

### 2. Setup Environment Files

```bash
# Create root .env
cp .env.example .env

# Create backend production env
cp backend/.env.production.example backend/.env.production

# Create frontend env
cp frontend/.env.example frontend/.env.production.local
```

### 3. Configure Secrets

Edit the `.env` file:
```bash
LETSENCRYPT_EMAIL=your-email@example.com
DB_ROOT_PASSWORD=secure-root-password
DB_DATABASE=xiangqi
DB_USERNAME=xiangqi
DB_PASSWORD=secure-db-password
REVERB_APP_KEY=generate-random-key
```

Edit `backend/.env.production`:
```bash
APP_ENV=production
APP_KEY=base64:your-app-key-here
APP_URL=https://api.chinesechess.online
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=xiangqi
DB_USERNAME=xiangqi
DB_PASSWORD=secure-db-password
REVERB_APP_KEY=same-as-above
```

Edit `frontend/.env.production.local`:
```bash
NEXT_PUBLIC_API_URL=https://api.chinesechess.online
NEXT_PUBLIC_PAYPAL_CLIENT_ID=your-paypal-client-id
```

### 4. Get/Generate Application Key

If you don't have an APP_KEY, generate one:

```bash
docker-compose run --rm backend php artisan key:generate --no-interaction
```

Then copy the generated key to `backend/.env.production`

### 5. Deploy with Docker

```bash
# Build and start all services
docker-compose up -d --build

# Wait for MySQL to be ready (check logs)
docker-compose logs mysql

# Run migrations
docker-compose exec backend php artisan migrate --force

# Seed initial data (optional)
docker-compose exec backend php artisan db:seed

# Cache configuration for production
docker-compose exec backend php artisan config:cache
docker-compose exec backend php artisan route:cache
```

### 6. Configure DNS

Point your domain A records to the server:
- `chinesechess.online` → Server IP
- `www.chinesechess.online` → Server IP
- `api.chinesechess.online` → Server IP
- `ws.chinesechess.online` → Server IP

### 7. Verify Deployment

```bash
# Check all services are running
docker-compose ps

# Check logs
docker-compose logs -f

# Test API endpoint
curl https://api.chinesechess.online/api/leaderboard

# Test frontend
curl https://chinesechess.online
```

## Maintenance

### Update Code
```bash
# Pull latest from git
git pull origin main

# Rebuild containers
docker-compose up -d --build

# Run any new migrations
docker-compose exec backend php artisan migrate --force
```

### View Logs
```bash
# All services
docker-compose logs -f

# Specific service
docker-compose logs -f backend
docker-compose logs -f frontend
docker-compose logs -f mysql
```

### Database Backup
```bash
# Backup database
docker-compose exec mysql mysqldump -u xiangqi -p xiangqi > backup.sql

# Restore database
docker-compose exec -T mysql mysql -u xiangqi -p xiangqi < backup.sql
```

### Clear Cache
```bash
docker-compose exec backend php artisan cache:clear
docker-compose exec backend php artisan config:clear
```

## Troubleshooting

### Port Already in Use
```bash
# Change ports in docker-compose.yml
# Or kill process using the port
lsof -i :80
```

### SSL Certificate Issues
Check ACME logs:
```bash
docker-compose logs acme
```

### Database Connection Failed
```bash
# Check MySQL is running
docker-compose exec mysql mysql -u root -p -e "SELECT 1"

# Check backend can reach MySQL
docker-compose exec backend ping mysql
```

### Permission Issues with Storage
```bash
docker-compose exec backend chmod -R 777 storage bootstrap/cache
```

## Production Checklist

- [ ] Environment variables configured with real secrets
- [ ] Database migrated and seeded
- [ ] DNS records pointing to server
- [ ] SSL certificate issued and active
- [ ] Backend API responding on api.chinesechess.online
- [ ] Frontend loading on chinesechess.online
- [ ] WebSocket (Reverb) configured and working
- [ ] Database backups configured
- [ ] Monitoring/logging setup (optional)
- [ ] Analytics/error tracking setup (optional)

## Support

For issues or questions, refer to:
- Backend: `backend/README.md`
- Frontend: `frontend/README.md`
- Database schema: `xiangqi-production-import.sql`
