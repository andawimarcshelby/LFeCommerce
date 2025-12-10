# Deployment Guide

Production deployment guide for the High-Volume LMS Reporting Module.

---

## Prerequisites

- Ubuntu 20.04+ or similar Linux server
- Docker 20.10+
- Docker Compose 2.0+
- Domain name with DNS configured
- SSL certificate (Let's Encrypt recommended)
- Minimum 4GB RAM, 2 CPU cores, 50GB disk

---

## Quick Deployment

For experienced users:

```bash
# 1. Clone repository
git clone https://github.com/andawimarcshelby/LFeCommerce.git
cd LFeCommerce

# 2. Configure environment
cp .env.example .env
nano .env  # Edit with production values

# 3. Deploy
docker-compose up -d
docker exec lms_reporting_api php artisan migrate --force
docker exec lms_reporting_api php artisan db:seed --class=RolePermissionSeeder

# 4. Create admin user
docker exec lms_reporting_api php artisan tinker
>>> User::create(['name' => 'Admin', 'email' => 'admin@your-domain.com', 'password' => bcrypt('secure-password')])
>>> User::first()->assignRole('admin')
```

---

## Detailed Deployment

### Step 1: Server Preparation

#### Update System
```bash
sudo apt update &&sudo apt upgrade -y
sudo reboot
```

#### Install Docker
```bash
#  Install dependencies
sudo apt install apt-transport-https ca-certificates curl software-properties-common -y

# Add Docker GPG key
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg

# Add Docker repository
echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

# Install Docker
sudo apt update
sudo apt install docker-ce docker-ce-cli containerd.io -y

# Start Docker
sudo systemctl start docker
sudo systemctl enable docker

# Add user to docker group
sudo usermod -aG docker $USER
newgrp docker
```

#### Install Docker Compose
```bash
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose
docker-compose --version
```

---

### Step 2: SSL Certificate

#### Using Let's Encrypt (Recommended)
```bash
# Install Certbot
sudo apt install certbot -y

# Generate certificate
sudo certbot certonly --standalone -d your-domain.com

# Certificates location:
# /etc/letsencrypt/live/your-domain.com/fullchain.pem
# /etc/letsencrypt/live/your-domain.com/privkey.pem

# Auto-renewal
sudo systemctl enable certbot.timer
```

---

### Step 3: Application Setup

#### Clone Repository
```bash
cd /var/www
sudo git clone https://github.com/andawimarcshelby/LFeCommerce.git lms-reporting
cd lms-reporting
sudo chown -R $USER:$USER .
```

#### Configure Environment
```bash
cp .env.example .env
nano .env
```

**Production `.env` Template**:
```env
# Application
APP_NAME="LMS Reporting"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=lms_reporting
DB_USERNAME=lms_user
DB_PASSWORD=CHANGE_THIS_STRONG_PASSWORD

# Redis
REDIS_HOST=redis
REDIS_PASSWORD=CHANGE_THIS_REDIS_PASSWORD
REDIS_PORT=6379

# Queue
QUEUE_CONNECTION=redis
HORIZON_BALANCE=auto
HORIZON_MAX_PROCESSES=10

# Mail (Production SMTP)
MAIL_MAILER=smtp
MAIL_HOST=smtp.your-email-provider.com
MAIL_PORT=587
MAIL_USERNAME=noreply@your-domain.com
MAIL_PASSWORD=your-smtp-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="${APP_NAME}"

# Security
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SANCTUM_STATEFUL_DOMAINS=your-domain.com
```

**Important**: Change ALL passwords and secrets!

---

### Step 4: Docker Configuration

#### Production docker-compose.yml
Create `docker-compose.prod.yml`:
```yaml
version: '3.8'

services:
  db:
    image: postgres:15
    restart: always
    environment:
      POSTGRES_DB: ${DB_DATABASE}
      POSTGRES_USER: ${DB_USERNAME}
      POSTGRES_PASSWORD: ${DB_PASSWORD}
    volumes:
      - db-data:/var/lib/postgresql/data
    networks:
      - lms-network

  redis:
    image: redis:7-alpine
    restart: always
    command: redis-server --requirepass ${REDIS_PASSWORD}
    volumes:
      - redis-data:/data
    networks:
      - lms-network

  api:
    build:
      context: ./backend
      dockerfile: Dockerfile
    restart: always
    environment:
      APP_ENV: production
    volumes:
      - ./backend:/var/www/html
      - storage-data:/var/www/html/storage
    depends_on:
      - db
      - redis
    networks:
      - lms-network

  worker:
    build:
      context: ./backend
      dockerfile: Dockerfile
    restart: always
    command: php artisan queue:work --sleep=3 --tries=3
    volumes:
      - ./backend:/var/www/html
      - storage-data:/var/www/html/storage
    depends_on:
      - db
      - redis
    networks:
      - lms-network

  client:
    build:
      context: ./frontend
      dockerfile: Dockerfile
    restart: always
    volumes:
      - ./frontend:/app
    networks:
      - lms-network

  web:
    image: nginx:alpine
    restart: always
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./.docker/web/nginx.conf:/etc/nginx/nginx.conf
      - ./frontend/dist:/var/www/html
      - /etc/letsencrypt:/etc/letsencrypt:ro
    depends_on:
      - api
      - client
    networks:
      - lms-network

volumes:
  db-data:
  redis-data:
  storage-data:

networks:
  lms-network:
    driver: bridge
```

---

### Step 5: Build and Deploy

```bash
# Build containers
docker-compose -f docker-compose.prod.yml build

# Start containers
docker-compose -f docker-compose.prod.yml up -d

# Check status
docker ps

# View logs
docker-compose -f docker-compose.prod.yml logs -f
```

---

### Step 6: Database Setup

```bash
# Run migrations
docker exec lms_reporting_api php artisan migrate --force

# Seed roles and permissions
docker exec lms_reporting_api php artisan db:seed --class=RolePermissionSeeder

# Create admin user
docker exec -it lms_reporting_api php artisan tinker
```

In Tinker:
```php
$admin = User::create([
    'name' => 'Administrator',
    'email' => 'admin@your-domain.com',
    'password' => Hash::make('your-secure-password'),
    'email_verified_at' => now(),
]);

$admin->assignRole('admin');
exit
```

---

### Step 7: Frontend Build

```bash
# Install dependencies
docker exec lms_reporting_client npm install

# Build for production
docker exec lms_reporting_client npm run build

# Output: frontend/dist/
```

---

### Step 8: Nginx Configuration

Edit `.docker/web/nginx.conf`:
```nginx
events {
    worker_connections 1024;
}

http {
    include /etc/nginx/mime.types;
    default_type application/octet-stream;

    # SSL Configuration
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    # Rate limiting
    limit_req_zone $binary_remote_addr zone=api:10m rate=60r/m;

    # Redirect HTTP to HTTPS
    server {
        listen 80;
        server_name your-domain.com;
        return 301 https://$server_name$request_uri;
    }

    # HTTPS Server
    server {
        listen 443 ssl http2;
        server_name your-domain.com;

        ssl_certificate /etc/letsencrypt/live/your-domain.com/fullchain.pem;
        ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;

        root /var/www/html;
        index index.html;

        # Frontend (React SPA)
        location / {
            try_files $uri $uri/ /index.html;
        }

        # API Backend
        location /api {
            limit_req zone=api burst=20 nodelay;
            proxy_pass http://api:8000;
            proxy_set_header Host $host;
            proxy_set_header X-Real-IP $remote_addr;
            proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
            proxy_set_header X-Forwarded-Proto $scheme;
        }

        # Horizon Dashboard
        location /horizon {
            proxy_pass http://api:8000;
            proxy_set_header Host $host;
        }
    }
}
```

Reload Nginx:
```bash
docker exec lms_reporting_web nginx -s reload
```

---

## Post-Deployment

### Verify Installation

1. **Check all containers running**:
   ```bash
   docker ps
   ```
   Expected: 6 containers (db, redis, api, worker, client, web)

2. **Test API**:
   ```bash
   curl https://your-domain.com/api/auth/login \
     -H "Content-Type: application/json" \
     -d '{"email":"admin@your-domain.com","password":"your-password"}'
   ```

3. **Test Frontend**:
   Open `https://your-domain.com` in browser

4. **Check Horizon**:
   Open `https://your-domain.com/horizon`

---

### Monitoring Setup

#### Laravel Horizon
Already included - access at `/horizon`

#### Log Monitoring
```bash
# Application logs
docker exec lms_reporting_api tail -f storage/logs/laravel.log

# Queue logs
docker exec lms_reporting_worker tail -f storage/logs/laravel.log

# Nginx logs
docker exec lms_reporting_web tail -f /var/log/nginx/access.log
```

#### Health Checks
Add to cron:
```bash
*/5 * * * * curl -f https://your-domain.com/api/health || echo "Health check failed"
```

---

### Backup Strategy

#### Database Backups
```bash
# Daily backup script
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
docker exec lms_reporting_db pg_dump -U lms_user lms_reporting | gzip > /backups/db_$DATE.sql.gz

# Keep only last 7 days
find /backups -name "db_*.sql.gz" -mtime +7 -delete
```

Add to crontab:
```bash
0 2 * * * /path/to/backup-db.sh
```

#### File Backups
```bash
# Backup generated reports
tar -czf /backups/reports_$(date +%Y%m%d).tar.gz /var/www/lms-reporting/backend/storage/app/reports/
```

---

### Maintenance

#### Update Application
```bash
cd /var/www/lms-reporting
git pull origin master
docker-compose -f docker-compose.prod.yml build
docker-compose -f docker-compose.prod.yml up -d
docker exec lms_reporting_api php artisan migrate --force
docker exec lms_reporting_client npm run build
```

#### Scale Workers
Edit `docker-compose.prod.yml`:
```yaml
worker:
  deploy:
    replicas: 3  # Run 3 worker processes
```

Restart:
```bash
docker-compose -f docker-compose.prod.yml up -d --scale worker=3
```

---

## Security Checklist

- [ ] Change all default passwords
- [ ] Enable SSL/TLS (HTTPS)
- [ ] Configure firewall (UFW)
- [ ] Disable debug mode (`APP_DEBUG=false`)
- [ ] Set secure session driver (Redis)
- [ ] Configure CORS properly
- [ ] Enable rate limiting
- [ ] Regular security updates
- [ ] Database backups automated
- [ ] Log file rotation enabled
- [ ] Horizon authentication configured

---

## Troubleshooting

### Containers Won't Start
```bash
# Check logs
docker-compose logs

# Rebuild
docker-compose down
docker-compose build --no-cache
docker-compose up -d
```

### Database Connection Failed
- Verify `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` in `.env`
- Check database container: `docker logs lms_reporting_db`
- Test connection: `docker exec lms_reporting_api php artisan tinker` → `DB::connection()->getPdo()`

### Queue Jobs Not Processing
- Check worker: `docker logs lms_reporting_worker`
- Restart worker: `docker restart lms_reporting_worker`
- Check Horizon: `/horizon`

### SSL Certificate Errors
- Verify certificate paths in `nginx.conf`
- Renew certificate: `sudo certbot renew`
- Check permissions: `sudo chmod 644 /etc/letsencrypt/live/your-domain.com/*`

---

## Performance Optimization

### Production Optimizations
```bash
# Optimize autoloader
docker exec lms_reporting_api composer install --optimize-autoloader --no-dev

# Cache config
docker exec lms_reporting_api php artisan config:cache

# Cache routes
docker exec lms_reporting_api php artisan route:cache

# Cache views
docker exec lms_reporting_api php artisan view:cache
```

### Database Tuning
Edit `postgresql.conf`:
```
shared_buffers = 512MB
effective_cache_size = 1GB
maintenance_work_mem = 128MB
max_connections = 50
```

---

**Deployment complete! Access your application at `https://your-domain.com`**

---

**Last Updated**: December 10, 2025
