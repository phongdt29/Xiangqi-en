#!/bin/bash

# Build and Deploy Xiangqi to Production
# Run this on production server with Docker installed

set -e

echo "=========================================="
echo "Xiangqi Build & Deploy Script"
echo "=========================================="

# Configuration
PROJECT_DIR="/var/www/xiangqi-en"
DOCKER_REGISTRY="docker.io"
DOCKER_USER="your-dockerhub-username"  # Change this
BACKEND_IMAGE="$DOCKER_REGISTRY/$DOCKER_USER/xiangqi-backend"
FRONTEND_IMAGE="$DOCKER_REGISTRY/$DOCKER_USER/xiangqi-frontend"
VERSION=$(date +%Y%m%d-%H%M%S)

cd "$PROJECT_DIR"

echo ""
echo "Step 1: Clone/Pull latest code"
if [ -d .git ]; then
    git pull origin main
else
    echo "Git repo not found, skipping pull"
fi

echo ""
echo "Step 2: Build Backend Docker Image"
docker build -t "$BACKEND_IMAGE:$VERSION" -t "$BACKEND_IMAGE:latest" backend/

echo ""
echo "Step 3: Build Frontend Docker Image"
# Create frontend Dockerfile if needed
cat > frontend/Dockerfile.prod << 'EOF'
FROM node:20-alpine AS builder
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

FROM nginx:alpine
COPY --from=builder /app/out /usr/share/nginx/html
COPY --from=builder /app/.next/static /usr/share/nginx/html/.next/static
EXPOSE 3000
CMD ["nginx", "-g", "daemon off;"]
EOF

docker build -f frontend/Dockerfile.prod -t "$FRONTEND_IMAGE:$VERSION" -t "$FRONTEND_IMAGE:latest" frontend/

echo ""
echo "Step 4: Push Images to Docker Hub (optional)"
read -p "Push images to Docker Hub? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    docker push "$BACKEND_IMAGE:$VERSION"
    docker push "$BACKEND_IMAGE:latest"
    docker push "$FRONTEND_IMAGE:$VERSION"
    docker push "$FRONTEND_IMAGE:latest"
    echo "✓ Images pushed to Docker Hub"
fi

echo ""
echo "Step 5: Stop & Remove Old Containers"
docker-compose down || true

echo ""
echo "Step 6: Update docker-compose.yml to use local images"
# Use local built images (no build context)
cat > docker-compose.prod.yml << EOF
version: '3.8'

services:
  mysql:
    image: mysql:8.0
    restart: unless-stopped
    environment:
      MYSQL_ROOT_PASSWORD: \${DB_ROOT_PASSWORD}
      MYSQL_DATABASE: \${DB_DATABASE}
      MYSQL_USER: \${DB_USERNAME}
      MYSQL_PASSWORD: \${DB_PASSWORD}
    volumes:
      - mysql_data:/var/lib/mysql
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost", "-u", "root", "-p\${DB_ROOT_PASSWORD}"]
      interval: 5s
      timeout: 5s
      retries: 20

  backend:
    image: $BACKEND_IMAGE:latest
    restart: unless-stopped
    env_file: ./backend/.env.production
    depends_on:
      mysql:
        condition: service_healthy
    ports:
      - "8000:80"
    environment:
      VIRTUAL_HOST: api.chinesechess.online
      VIRTUAL_PORT: 80
      LETSENCRYPT_HOST: api.chinesechess.online
      LETSENCRYPT_EMAIL: \${LETSENCRYPT_EMAIL}
    volumes:
      - backend_storage:/var/www/html/storage

  frontend:
    image: $FRONTEND_IMAGE:latest
    restart: unless-stopped
    ports:
      - "3000:3000"
    environment:
      VIRTUAL_HOST: chinesechess.online,www.chinesechess.online
      VIRTUAL_PORT: "3000"
      LETSENCRYPT_HOST: chinesechess.online,www.chinesechess.online
      LETSENCRYPT_EMAIL: \${LETSENCRYPT_EMAIL}

  proxy:
    image: nginxproxy/nginx-proxy:1
    restart: unless-stopped
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - /var/run/docker.sock:/tmp/docker.sock:ro
      - certs:/etc/nginx/certs
      - vhost:/etc/nginx/vhost.d
      - html:/usr/share/nginx/html

  acme:
    image: nginxproxy/acme-companion:2
    restart: unless-stopped
    depends_on:
      - proxy
    environment:
      DEFAULT_EMAIL: \${LETSENCRYPT_EMAIL}
      NGINX_PROXY_CONTAINER: proxy
    volumes:
      - certs:/etc/nginx/certs
      - vhost:/etc/nginx/vhost.d
      - html:/usr/share/nginx/html
      - acme:/etc/acme.sh
      - /var/run/docker.sock:/var/run/docker.sock:ro

volumes:
  mysql_data:
  backend_storage:
  certs:
  vhost:
  html:
  acme:
EOF

echo ""
echo "Step 7: Start Containers with Built Images"
docker-compose -f docker-compose.prod.yml up -d

echo ""
echo "Step 8: Run Migrations"
sleep 5
docker-compose -f docker-compose.prod.yml exec backend php artisan migrate --force

echo ""
echo "Step 9: Run Seeders"
docker-compose -f docker-compose.prod.yml exec backend php artisan db:seed

echo ""
echo "✓ =========================================="
echo "✓ Build & Deploy Complete!"
echo "✓ =========================================="
echo ""
echo "Backend Image: $BACKEND_IMAGE:$VERSION"
echo "Frontend Image: $FRONTEND_IMAGE:$VERSION"
echo ""
echo "Access your site:"
echo "  Frontend: https://chinesechess.online"
echo "  API: https://api.chinesechess.online"
echo ""
echo "View logs:"
echo "  docker-compose -f docker-compose.prod.yml logs -f"
