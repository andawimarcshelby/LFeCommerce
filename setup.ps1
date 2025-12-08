# Quick Fix Script - Run this to complete setup

Write-Host "=== LMS Reporting System Setup ===" -ForegroundColor Cyan

# 1. Check if containers are running
Write-Host "`n[1/6] Checking Docker containers..." -ForegroundColor Yellow
docker-compose ps

# 2. Copy .env file if needed
Write-Host "`n[2/6] Setting up environment file..." -ForegroundColor Yellow
if (-not (Test-Path "backend\.env")) {
    Copy-Item "backend\.env.example" "backend\.env"
    Write-Host "  ✓ Created backend\.env" -ForegroundColor Green
} else {
    Write-Host "  ✓ backend\.env already exists" -ForegroundColor Green
}

# 3. Generate app key
Write-Host "`n[3/6] Generating application key..." -ForegroundColor Yellow
docker-compose exec -T api php artisan key:generate --force

# 4. Run migrations  
Write-Host "`n[4/6] Running database migrations..." -ForegroundColor Yellow
docker-compose exec -T api php artisan migrate --force

# 5. Seed database (this takes 8-12 minutes)
Write-Host "`n[5/6] Seeding database with 10M+ rows (this will take 8-12 minutes)..." -ForegroundColor Yellow
Write-Host "  ⏳ Please wait..." -ForegroundColor Cyan
docker-compose exec -T api php artisan db:seed --class=LmsActivitySeeder --force

# 6. Check row counts
Write-Host "`n[6/6] Verifying data..." -ForegroundColor Yellow
docker-compose exec -T db psql -U homestead lms_reporting -c "SELECT COUNT(*) as course_events FROM course_events;"

Write-Host "`n✅ Setup Complete!`n" -ForegroundColor Green
Write-Host "Access the application at:" -ForegroundColor Cyan
Write-Host "  Frontend:  http://localhost:3000" -ForegroundColor White
Write-Host "  API:       http://localhost:8000" -ForegroundColor White
Write-Host "  Mailhog:   http://localhost:8025`n" -ForegroundColor White
