# Quick Setup Guide

## Prerequisites Check
✅ Docker Desktop installed and running  
✅ At least 8GB RAM  
✅ 10GB free disk space  

## Setup Steps

### 1. Copy Environment File
```powershell
copy backend\.env.example backend\.env
```

### 2. Generate Application Key
```powershell
cd backend
composer install
php artisan key:generate
cd ..
```

### 3. Start Docker Containers
```powershell
docker-compose up -d
```

Wait for all services to start (check with `docker-compose ps`).

### 4. Install Dependencies

**Backend:**
```powershell
docker-compose exec api composer install
docker-compose exec api php artisan octane:install --server=swoole
```

**Frontend:**
```powershell
docker-compose exec client npm install
```

### 5. Run Database Migrations
```powershell
docker-compose exec api php artisan migrate
```

### 6. Seed Database (10M+ Rows - Takes 8-12 minutes)
```powershell
docker-compose exec api php artisan db:seed --class=LmsActivitySeeder
```

### 7. Access the Application

- **Frontend**: http://localhost:3000
- **API**: http://localhost:8000
- **Mailhog**: http://localhost:8025

## Verify Everything Works

### Check Database
```powershell
docker-compose exec db psql -U homestead lms_reporting -c "SELECT COUNT(*) FROM course_events;"
```
Should return ~10,000,000

### Test API
Open http://localhost:8000/api/reports/preview in browser or:
```powershell
curl -X POST http://localhost:8000/api/reports/preview -H "Content-Type: application/json" -d "{\"report_type\":\"detail\",\"filters\":{\"date_from\":\"2024-01-01\",\"date_to\":\"2024-12-31\"}}"
```

### Test Frontend
1. Go to http://localhost:3000
2. Navigate to "Reports"
3. Adjust date filters
4. Click "Preview Report" → should see data in <500ms
5. Click "Export as PDF" → job created
6. Go to "Export Center" → see job progress
7. Download when complete

## Common Issues

**Docker not starting:**
- Ensure Docker Desktop is running
- Check ports 3000, 8000, 5432, 6379 are not in use

**Database connection error:**
- Wait 30 seconds for PostgreSQL to fully start
- Run `docker-compose logs db` to check for errors

**Octane not starting:**
- Check logs: `docker-compose logs api`
- Ensure Swoole extension is installed

**Frontend not loading:**
- Run `docker-compose exec client npm install`
- Check logs: `docker-compose logs client`

## Stopping the System

```powershell
# Stop containers (keeps data)
docker-compose stop

# Stop and remove containers (keeps volumes)
docker-compose down

# Remove everything including data
docker-compose down -v
```

## Next Steps

1. Explore the Dashboard at http://localhost:3000
2. Generate your first report
3. Monitor export jobs in real-time
4. Check README.md for detailed documentation
