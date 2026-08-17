# Build & Deploy on Production Host

## Quick Start (Dễ nhất)

Chạy script này trên production server:

```bash
# SSH vào server
ssh user@your-server.com

# Download code
cd /var/www
git clone https://github.com/phongdt29/Xiangqi-en.git
cd Xiangqi-en

# Copy environment files
cp .env.example .env
cp backend/.env.production.example backend/.env.production

# Edit .env files với production values
nano .env
nano backend/.env.production

# Chạy build script
chmod +x deploy/build-and-deploy.sh
./deploy/build-and-deploy.sh
```

Script sẽ:
1. ✅ Clone latest code
2. ✅ Build backend Docker image
3. ✅ Build frontend Docker image
4. ✅ Run containers
5. ✅ Setup database
6. ✅ Ready to use!

---

## Manual Steps (Nếu script bị lỗi)

### **1. Setup Server**

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install Docker
sudo apt install docker.io docker-compose -y

# Add current user to docker group (no need sudo)
sudo usermod -aG docker $USER
newgrp docker

# Verify Docker works
docker ps
```

### **2. Clone Code**

```bash
cd /var/www
git clone https://github.com/phongdt29/Xiangqi-en.git
cd Xiangqi-en
```

### **3. Configure Environment**

```bash
# Create .env
cp .env.example .env
nano .env

# Fill in:
# LETSENCRYPT_EMAIL=your@email.com
# DB_ROOT_PASSWORD=strong-password
# DB_DATABASE=xiangqi
# DB_USERNAME=xiangqi
# DB_PASSWORD=strong-password
# REVERB_APP_KEY=random-key

# Create backend .env.production
cp backend/.env.production.example backend/.env.production
nano backend/.env.production

# Fill in production values:
# APP_ENV=production
# APP_KEY=base64:generated-key
# APP_URL=https://api.chinesechess.online
# DB_HOST=mysql
# DB_DATABASE=xiangqi
# DB_USERNAME=xiangqi
# DB_PASSWORD=same-as-above
# CORS_ALLOWED_ORIGINS=https://chinesechess.online,https://www.chinesechess.online
```

### **4. Generate APP_KEY**

```bash
# Build backend first
docker-compose build backend

# Generate key
docker-compose run --rm backend php artisan key:generate --no-interaction

# Copy key to backend/.env.production (APP_KEY=...)
```

### **5. Build Docker Images**

```bash
# Build all images
docker-compose build --no-cache

# Or build specific services:
docker-compose build --no-cache backend
docker-compose build --no-cache frontend
```

### **6. Start Containers**

```bash
# Start all services
docker-compose up -d

# Check status
docker-compose ps

# View logs
docker-compose logs -f
```

### **7. Setup Database**

```bash
# Wait for MySQL to be ready
sleep 10

# Run migrations
docker-compose exec backend php artisan migrate --force

# Seed data (10 users, 4 puzzles)
docker-compose exec backend php artisan db:seed

# Cache config
docker-compose exec backend php artisan config:cache
```

### **8. Configure DNS**

Point these domains to your server IP:
```
chinesechess.online → YOUR_SERVER_IP
www.chinesechess.online → YOUR_SERVER_IP
api.chinesechess.online → YOUR_SERVER_IP
ws.chinesechess.online → YOUR_SERVER_IP
```

### **9. Verify Installation**

```bash
# Check containers
docker-compose ps

# Test API
curl https://api.chinesechess.online/api/leaderboard

# Test frontend
curl https://chinesechess.online

# View logs
docker-compose logs backend | tail -20
```

---

## Troubleshooting

### **Build fails - Memory**
```bash
# Increase Docker memory limit
# Then rebuild:
docker-compose build --no-cache backend
```

### **Build fails - Disk space**
```bash
docker system prune -a
docker volume prune
df -h  # Check space
```

### **Port already in use**
```bash
# Stop containers
docker-compose down -v

# Or change ports in docker-compose.yml
# Then restart:
docker-compose up -d
```

### **Database connection error**
```bash
# Check DB_HOST=mysql (not localhost)
# Check credentials match
# Restart mysql:
docker-compose restart mysql
docker-compose logs mysql
```

### **CORS error**
```bash
# Check CORS_ALLOWED_ORIGINS in backend/.env.production
# Must include: https://chinesechess.online
# Restart backend:
docker-compose restart backend
```

### **Certificate not issued**
```bash
# Let's Encrypt takes time
# Check logs:
docker-compose logs acme

# Force retry:
docker-compose exec acme /etc/acme.sh/acme.sh --renew -d chinesechess.online
```

---

## Daily Operations

### **View Logs**
```bash
docker-compose logs -f backend
docker-compose logs -f frontend
docker-compose logs -f mysql
```

### **Backup Database**
```bash
docker-compose exec -T mysql mysqldump -u xiangqi -p"password" xiangqi > backup-$(date +%Y%m%d).sql
```

### **Restore Database**
```bash
docker-compose exec -T mysql mysql -u xiangqi -p"password" xiangqi < backup-20260817.sql
```

### **Update Code & Redeploy**
```bash
# Pull latest
git pull origin main

# Rebuild images
docker-compose build --no-cache

# Restart
docker-compose down
docker-compose up -d

# Run migrations (if any new ones)
docker-compose exec backend php artisan migrate --force
```

### **Check Database**
```bash
docker-compose exec backend php artisan tinker
>>> User::count()
=> 10

>>> Puzzle::count()
=> 4
```

---

## Files Structure

```
/var/www/xiangqi-en/
├── backend/              # Laravel backend
├── frontend/             # Next.js frontend
├── deploy/              # Deployment guides & scripts
├── docker-compose.yml   # Container orchestration
├── .env                 # Environment variables
├── .env.example         # Template
└── Dockerfile           # (in backend/)
```

---

## Security Notes

🔒 **Important:**
- ✅ Change all default passwords
- ✅ Use strong DB passwords
- ✅ Set APP_DEBUG=false in production
- ✅ Keep APP_ENV=production
- ✅ Use HTTPS (Let's Encrypt auto setup)
- ✅ Restrict firewall to needed ports
- ✅ Enable SSH key auth (no password login)

---

## Support

If errors occur, check:
1. `docker-compose logs backend`
2. `docker-compose logs mysql`
3. `docker-compose ps` (all running?)
4. Disk space: `df -h`
5. Memory: `free -h`

**Success! 🎉 Your site is live on production!**
