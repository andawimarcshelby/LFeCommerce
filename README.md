# High-Volume LMS Reporting System

A production-grade reporting module built with Laravel 12, React, Docker, PostgreSQL, and Octane capable of processing 10M+ rows and generating 1,000+ page PDFs.

## 🎯 Features

- **High-Volume Data Processing**: Handles 10M+ course activity events with table partitioning
- **Multiple Report Types**:
  - Detail Reports (raw activity events)
  - Summary Reports (engagement statistics with charts)
  - Top-N Reports (rankings and exceptions)
  - Per-Student Booklets (1,000+ page PDFs with TOC)
- **Async Export Generation**: Background jobs with progress tracking
- **Formats**: PDF (Browsershot) and Excel (streaming)
- **Real-time UI**: React with virtualized grids and live job status
- **Performance**: p95 < 500ms for preview queries, optimized with Octane

## 📋 Prerequisites

- **Docker Desktop** (Windows/Mac/Linux)
- **8GB RAM minimum**
- **10GB free disk space**

## 🚀 Quick Start

### 1. Clone/Navigate to Project
```powershell
cd "c:\PPK LMS"
```

### 2. Copy Environment File
```powershell
copy backend\.env.example backend\.env
```

### 3. Generate Application Key
```powershell
cd backend
php artisan key:generate
cd ..
```

### 4. Start Docker Containers
```powershell
docker-compose up -d
```

This will start all 7 services:
- **db**: PostgreSQL 15
- **redis**: Redis 7
- **api**: Laravel + Octane (Swoole)
- **worker**: Queue workers with Horizon
- **client**: React dev server (Vite)
- **web**: Nginx
- **mailhog**: Email testing

### 5. Install Dependencies (First Time Only)
```powershell
# Laravel dependencies
docker-compose exec api composer install

# Install Octane
docker-compose exec api php artisan octane:install --server=swoole

# Install Horizon
docker-compose exec api php artisan horizon:install

# Publish configurations
docker-compose exec api php artisan vendor:publish --tag=excel-config
docker-compose exec api php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"

# Frontend dependencies
docker-compose exec client npm install
```

### 6. Run Migrations
```powershell
docker-compose exec api php artisan migrate
```

This creates:
- `course_events` (partitioned by month)
- `auth_events`, `submissions`, `grading_audits`
- Dimension tables (students, courses, terms, instructors)
- Reporting tables (`course_daily_activity`, `student_course_engagement`)
- `report_jobs`, `report_presets`

### 7. Seed Database (10M+ Rows)
```powershell
docker-compose exec api php artisan db:seed --class=LmsActivitySeeder
```

⏱️ This takes **8-12 minutes** and generates:
- 5,000 students
- 100 courses across 4 terms
- 200 instructors
- 10,000,000+ course events
- 500,000+ submissions
- 100,000+ auth events
- 50,000+ grading audits

### 8. Access the Application

- **Frontend**: http://localhost:3000
- **API**: http://localhost:8000
- **Horizon (Queue Dashboard)**: http://localhost:8000/horizon
- **Mailhog (Emails)**: http://localhost:8025

## 🎨 Using the System

### Generate a Report

1. Open http://localhost:3000
2. Select filters (date range, term, courses, event types)
3. Click **Preview Report** to see first 100 rows (<500ms)
4. Click **Export as PDF** or **Export as Excel**
5. Monitor progress in **Export Center**
6. Download when complete

### Report Types

- **Detail Report**: Raw event listing with joins
- **Summary Report**: Daily/course aggregations with charts
- **Top-N Report**: Most active students, high-engagement courses
- **Per-Student Report**: Complete activity booklet (1,000+ pages)

## 🛠️ Development

### View Logs
```powershell
# All services
docker-compose logs -f

# Specific service
docker-compose logs -f api
docker-compose logs -f worker
```

### Run Tests
```powershell
# Unit tests
docker-compose exec api php artisan test

# Feature tests (preview performance)
docker-compose exec api php artisan test --filter=ReportPreviewTest

# Load tests with k6
docker run --rm -i grafana/k6 run - < tests/k6/preview-load-test.js
```

### Access Database
``powershell
docker-compose exec db psql -U homestead lms_reporting

# Check row counts
SELECT COUNT(*) FROM course_events;
SELECT COUNT(*) FROM students;
SELECT COUNT(*) FROM submissions;
```

### Stop Services
```powershell
# Stop but keep data
docker-compose stop

# Stop and remove containers (keeps volumes)
docker-compose down

# Remove everything including data
docker-compose down -v
```

## 📊 Performance Benchmarks

- **Preview Queries**: p95 < 500ms (with 10M rows)
- **PDF Generation**: 1,000-page PDF in < 5 minutes
- **Excel Export**: 1M rows in < 3 minutes
- **Queue Processing**: 3-5 concurrent exports supported

## 🏗️ Architecture

```
┌─────────────┐      ┌──────────────┐      ┌─────────────┐
│   React     │─────▶│    Nginx     │─────▶│  Laravel    │
│   (Vite)    │      │   (Proxy)    │      │  + Octane   │
│   Port 3000 │      │   Port 80    │      │  Port 8000  │
└─────────────┘      └──────────────┘      └──────┬──────┘
                                                   │
                           ┌───────────────────────┼───────────────┐
                           │                       │               │
                     ┌─────▼─────┐         ┌──────▼──────┐  ┌─────▼─────┐
                     │ PostgreSQL │         │    Redis    │  │  Worker   │
                     │  (10M rows)│         │ (Queue/Cache│  │ (Horizon) │
                     │  Port 5432 │         │  Port 6379) │  └───────────┘
                     └────────────┘         └─────────────┘
```

## 📁 Project Structure

```
c:\PPK LMS\
├── docker-compose.yml
├── .docker/
│   ├── api/Dockerfile
│   ├── worker/Dockerfile
│   ├── client/Dockerfile
│   └── web/nginx.conf
├── backend/                    # Laravel 11
│   ├── app/
│   │   ├── Models/            # CourseEvent, Student, ReportJob, etc.
│   │   ├── Services/          # ReportQueryBuilder, PdfGenerator, etc.
│   │   ├── Jobs/              # GenerateReportExportJob
│   │   └── Http/Controllers/  # ReportController, AdminReportController
│   ├── database/
│   │   ├── migrations/        # 7 migration files
│   │   └── seeders/           # LmsActivitySeeder
│   └── config/                 # octane.php, queue.php, etc.
└── frontend/                   # React + Vite
    ├── src/
    │   ├── components/        # FilterForm, DataGrid, ExportJobsCenter
    │   ├── hooks/             # useReportPreview, useExportJobs
    │   └── services/          # api.js
    └── tailwind.config.js
```

## 🔧 Troubleshooting

### Docker Issues
```powershell
# Rebuild containers
docker-compose build --no-cache

# Check service health
docker-compose ps
```

### Database Connection Errors
```powershell
# Ensure DB is healthy
docker-compose exec db pg_isready -U homestead

# Reset migrations
docker-compose exec api php artisan migrate:fresh --seed
```

### Octane Not Starting
```powershell
# Check Swoole is installed
docker-compose exec api php -m | findstr swoole

# Restart with verbose output
docker-compose exec api php artisan octane:start --host=0.0.0.0
```

## 📖 Documentation

- **API Endpoints**: See `docs/API.md`
- **Performance Tuning**: See `docs/PERFORMANCE_TUNING.md`
- **Implementation Plan**: See `.gemini/antigravity/brain/.../implementation_plan.md`

## 🔐 Security Notes

- Default credentials are for **development only**
- Change `DB_PASSWORD` and `APP_KEY` for production
- Enable HTTPS and set `SESSION_SECURE_COOKIE=true`
- Configure CORS properly for production frontend
- Use S3 for report storage in production

## 📝 License

This is an educational project for demonstrating high-volume reporting systems.

---

**Built with** Laravel 11 • React 18 • PostgreSQL 15 • Docker • Octane • Swoole
