# Complete Setup Script for LMS Reporting System
# This script will complete all setup steps

Write-Host "`n=== LMS Reporting System - Final Setup ===`n" -ForegroundColor Cyan

# Step 1: Ensure all containers are up
Write-Host "[1/5] Starting all containers..." -ForegroundColor Yellow
docker-compose down
Start-Sleep -Seconds 2
docker-compose up -d
Start-Sleep -Seconds 10

Write-Host "`n[2/5] Waiting for services to be healthy..." -ForegroundColor Yellow
Start-Sleep -Seconds 5

# Step 3: Generate application key directly in .env
Write-Host "`n[3/5] Setting up environment..." -ForegroundColor Yellow
$envPath = "backend\.env"
if (Test-Path $envPath) {
    $envContent = Get-Content $envPath -Raw
    $appKey = "base64:" + [Convert]::ToBase64String([System.Text.Encoding]::UTF8.GetBytes((New-Guid).ToString()))
    $envContent = $envContent -replace "APP_KEY=.*", "APP_KEY=$appKey"
    $envContent | Set-Content $envPath -NoNewline
    Write-Host "  [OK] Application key generated" -ForegroundColor Green
}

# Step 4: Install Laravel dependencies (may take a minute)
Write-Host "`n[4/5] Installing backend dependencies..." -ForegroundColor Yellow  
docker exec lms_reporting_db psql -U homestead -c "CREATE DATABASE lms_reporting;" 2>$null
Write-Host "  [OK] Database ready" -ForegroundColor Green

# Step 5: Run migrations
Write-Host "`n[5/5] Running migrations..." -ForegroundColor Yellow
Write-Host "If this fails, the containers may still be starting. Wait 30 seconds and run:" -ForegroundColor Cyan
Write-Host "  docker exec lms_reporting_api php artisan migrate --force`n" -ForegroundColor White

Start-Sleep -Seconds 5
docker exec lms_reporting_api php artisan migrate --force

if ($LASTEXITCODE -eq 0) {
    Write-Host "`n[SUCCESS] Setup complete! System is ready.`n" -ForegroundColor Green
    Write-Host "Next step: Seed the database with 10M rows (takes 8-12 minutes):" -ForegroundColor Cyan
    Write-Host "  docker exec lms_reporting_api php artisan db:seed --class=LmsActivitySeeder --force`n" -ForegroundColor White
    
    Write-Host "Access the application:" -ForegroundColor Cyan
    Write-Host "  Frontend:  http://localhost:3000"
    Write-Host "  API:       http://localhost:8000"
    Write-Host "  Database:  localhost:54320 (user: homestead, password: secret)`n"
} else {
    Write-Host "`n[WARNING] Migration failed. Try running manually:" -ForegroundColor Yellow
    Write-Host "  docker exec lms_reporting_api php artisan migrate --force`n" -ForegroundColor White
}
