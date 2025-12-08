# Complete Testing Guide - From Fresh Start

## 🎯 Prerequisites
- Docker Desktop running
- PowerShell terminal
- All containers stopped (if previously running)

---

## 📋 Complete Fresh Start Test (15-20 minutes)

### Step 1: Clean Everything
```powershell
cd "c:\PPK LMS"

# Stop and remove all containers + volumes
docker-compose down -v

# Verify nothing is running
docker-compose ps
```

### Step 2: Start All Services
```powershell
# Build and start all containers
docker-compose up -d

# Wait for services to be healthy (30 seconds)
Start-Sleep -Seconds 30

# Check all containers are running
docker-compose ps
```

**Expected Output:** 8 containers running (db, redis, api, worker, client, web, mailhog)

### Step 3: Generate Application Key
```powershell
# Generate Laravel encryption key
docker run --rm -v "${PWD}/backend:/app" -w /app php:8.2-cli php artisan key:generate --force

# Restart API to pick up key
docker-compose restart api worker

# Wait for restart
Start-Sleep -Seconds 10
```

### Step 4: Run Database Migrations
```powershell
# Run all 8 migrations (creates partitioned tables)
docker exec lms_reporting_api php artisan migrate --force
```

**Expected Output:**
```
INFO  Preparing database.
Creating migration table ... DONE
INFO  Running migrations.
2024_01_01_000001_create_course_events_table ... DONE
2024_01_01_000002_create_auth_events_table ... DONE
... (8 migrations total)
```

### Step 5: Seed Database with 10M Rows (8-12 minutes)
```powershell
# Start seeding
docker exec lms_reporting_api php artisan db:seed --class=LmsActivitySeeder --force
```

**Expected Output:**
```
Starting LMS Activity Seeder...
Target: 10,000,000 events

Seeding terms...
✓ Created 4 terms

Seeding students...
✓ Created 5,000 students

Seeding instructors...
✓ Created 200 instructors

Seeding courses...
✓ Created 100 courses

... (continues for 8-12 minutes)

Database seeding complete!
```

### Step 6: Verify Data
```powershell
# Check course events count
docker exec lms_reporting_db psql -U homestead lms_reporting -c "SELECT COUNT(*) FROM course_events;"
```

**Expected:** ~10,000,000

```powershell
# Check students
docker exec lms_reporting_db psql -U homestead lms_reporting -c "SELECT COUNT(*) FROM students;"
```

**Expected:** 5,000

### Step 7: Seed Roles & Permissions (Optional for Auth Testing)
```powershell
docker exec lms_reporting_api php artisan db:seed --class=RolesAndPermissionsSeeder --force
```

**Expected Output:**
```
Roles and permissions seeded successfully!
Demo users created:
  Admin: admin@university.edu
  Faculty: faculty@university.edu
  Student: student@university.edu
```

---

## 🌐 Test the Application

### 1. Frontend Access
Open your browser: **http://localhost:3000**

**Expected:** Professional university-themed dashboard with:
- Blue gradient header with university seal
- Stat cards showing 10M+ events, 5K students, 100 courses
- Quick action buttons
- System capabilities list

### 2. Test Dashboard
Navigate through the UI:
1. Click **"Dashboard"** - See overview
2. Click **"Reports"** - See filter form
3. Click **"Export Center"** - See empty state (no jobs yet)

### 3. Test Report Preview

**On Reports Page:**
1. Set date range: `2024-01-01` to `2024-12-31`
2. Select report type: "Detail Report"
3. Click **"Preview Report"**

**Expected:**
- Query completes in <500ms
- Table shows first 100 rows
- Stats show ~10M total rows
- Query time shown in badge

### 4. Test PDF Export

**On Reports Page:**
1. Click **"Export as PDF"**

**Expected:**
- Success message: "Export job created!"
- Redirected to Export Center automatically

### 5. Test Export Center

**On Export Center:**
1. See your PDF export job with "Running" status
2. Watch progress bar update (refreshes every 5 seconds)
3. Wait for "Completed" status

**Expected:**
- Progress: 0% → 25% → 50% → 75% → 100%
- Status changes: Queued → Running → Completed
- Download button appears when done

### 6. Download Report
1. Click **"Download"** button on completed job
2. PDF file downloads

**Expected:**
- File downloads successfully
- File size shown in Export Center
- Can open PDF and see data

### 7. Test Excel Export

**On Reports Page:**
1. Click **"Export as Excel"**
2. Go to Export Center
3. Watch progress
4. Download when complete

---

## 🧪 Advanced Testing

### Performance Test (Preview Query Speed)
```powershell
# Time a preview query
Measure-Command {
  curl -X POST http://localhost:8000/api/reports/preview `
    -H "Content-Type: application/json" `
    -d '{"report_type":"detail","filters":{"date_from":"2024-01-01","date_to":"2024-12-31"},"page":1,"per_page":100}'
}
```

**Expected:** TotalMilliseconds < 500

### Check Horizon Queue Monitor
1. Open: **http://localhost:8000/horizon**
2. See queue dashboard
3. Check completed jobs
4. Monitor throughput

### Database Performance
```powershell
# Check partition info
docker exec lms_reporting_db psql -U homestead lms_reporting -c "
SELECT schemaname, tablename FROM pg_tables 
WHERE tablename LIKE 'course_events_%' 
ORDER BY tablename;"
```

**Expected:** 24 partitions (course_events_2024_01 through course_events_2025_12)

---

## ✅ Success Checklist

- [ ] All 8 Docker containers running
- [ ] Database has 10M+ course_events
- [ ] Frontend loads at http://localhost:3000
- [ ] Dashboard shows correct stats
- [ ] Report preview works (<500ms)
- [ ] PDF export creates job
- [ ] Progress tracking works
- [ ] Download completes successfully
- [ ] Excel export works
- [ ] Horizon dashboard accessible

---

## 🐛 Troubleshooting

### Frontend doesn't load
```powershell
docker logs lms_reporting_client --tail=20
docker-compose restart client
```

### API errors
```powershell
docker logs lms_reporting_api --tail=50
# Check for APP_KEY error - regenerate if needed
docker run --rm -v "${PWD}/backend:/app" -w /app php:8.2-cli php artisan key:generate --force
docker-compose restart api
```

### Worker not processing jobs
```powershell
docker logs lms_reporting_worker --tail=30
# Should see "Horizon started successfully"
docker-compose restart worker
```

### Database connection issues
```powershell
# Check database is running
docker-compose ps db
# Verify connection
docker exec lms_reporting_db psql -U homestead -l
```

---

## 🔄 Quick Reset for Re-testing

```powershell
# Quick reset (keeps Docker images)
docker-compose down
docker-compose up -d
Start-Sleep -Seconds 30

# Re-run migrations and seed
docker run --rm -v "${PWD}/backend:/app" -w /app php:8.2-cli php artisan key:generate --force
docker-compose restart api worker
Start-Sleep -Seconds 10
docker exec lms_reporting_api php artisan migrate:fresh --force
docker exec lms_reporting_api php artisan db:seed --class=LmsActivitySeeder --force
```

---

## ⏱️ Expected Timings

| Task | Duration |
|------|----------|
| Docker startup | 30 sec |
| Migrations | 10 sec |
| Seeding 10M rows | 8-12 min |
| Preview query | <500ms |
| PDF generation (small) | 1-2 min |
| PDF generation (1000 pages) | 5-10 min |
| Excel export (100K rows) | 30 sec |
| Excel export (1M rows) | 2-3 min |

---

**You're ready to demonstrate the full system!** 🎉
