# Manual Setup Steps - Run these one at a time

## Current Status
All Docker containers should be running. You can verify in Docker Desktop.

## Step 1: Verify Containers Running
```powershell
docker-compose ps
```
You should see all 8 containers with STATUS "Up" or "running"

## Step 2: Wait for API to Fully Start
The API container takes 10-15 seconds to start Octane/Swoole.

Wait 20 seconds, then check logs:
```powershell
docker logs lms_reporting_api --tail=10
```

You should see: **"INFO  Server running…."**

## Step 3: Run Migrations
Once the server is running, run:
```powershell
docker exec lms_reporting_api php artisan migrate --force
```

**If this fails** with "container not running", wait another 10 seconds and try again.

## Step 4: Verify Migrations Worked
```powershell
docker exec lms_reporting_db psql -U homestead lms_reporting -c "\dt"
```

You should see a list of tables including:
- course_events
- students
- courses
- terms
- report_jobs

## Step 5: Seed Database (8-12 minutes)
```powershell
docker exec lms_reporting_api php artisan db:seed --class=LmsActivitySeeder --force
```

This will generate:
- 10,000,000+ course events
- 500,000+ submissions
- 100,000+ auth events
- 5,000 students
- 100 courses

## Step 6: Verify Data
```powershell
docker exec lms_reporting_db psql -U homestead lms_reporting -c "SELECT COUNT(*) FROM course_events;"
```

Should return approximately 10,000,000

## Step 7: Access the Application
- **Frontend:** http://localhost:3000
- **API:** http://localhost:8000/api/reports/exports
- **Database:** localhost:54320 (pgAdmin or any PostgreSQL client)

## Troubleshooting

### If API container keeps restarting:
1. Check logs: `docker logs lms_reporting_api`
2. Look for any PHP errors
3. Verify backend/.env has APP_KEY set

### If database connection fails:
1. Verify DB is running: `docker-compose ps db`
2. Check database exists: `docker exec lms_reporting_db psql -U homestead -l`
3. Database should be "lms_reporting"

### If frontend shows errors:
1. Check client logs: `docker logs lms_reporting_client`
2. Restart client: `docker-compose restart client`
3. Access directly: http://localhost:3000

### Complete Reset (if needed):
```powershell
docker-compose down -v
docker-compose up -d
# Then repeat steps 2-6
```
