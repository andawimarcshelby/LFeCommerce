# Docker Build & Setup Issues - Solutions

##Issue 1: Swoole compilation failed ✅ FIXED
**Error:** `Package 'libbrotlienc' not found`
**Solution:** Added `libbrotli-dev` to Dockerfiles
**Status:** ✅ Resolved - Docker build now completes successfully

## Issue 2: Port 5432 already in use ✅ FIXED  
**Error:** `Bind for 0.0.0.0:5432 failed`
**Solution:** Changed PostgreSQL port to 54320 in docker-compose.yml
**Status:** ✅ Resolved - All containers now start

## Issue 3: PHP syntax error in migration ✅ FIXED
**Error:** `unexpected token 'monthStr'`
**Solution:** Removed extra space: `$ monthStr` → `$monthStr`
**Status:** ✅ Resolved

## Issue 4: API container config error ⚠️ IN PROGRESS
**Error:** `array_merge(): Argument #2 must be of type array, int given`
**Location:** Laravel bootstrap/configuration
**Impact:** API container crashes on startup

**Possible Causes:**
1. Laravel 11 compatibility issue with routes
2. Missing or incorrect .env file
3. Config cache from old version

**Recommended Solutions:**
1. Generate fresh `.env` from `.env.example`
2. Run `php artisan config:clear` in container
3. Check bootstrap/app.php for Laravel 11 syntax

## Next Steps

Run this command to debug:
```powershell
# View full error log
docker-compose logs api --tail=100

# Access container and debug
docker-compose exec api bash
php artisan config:clear
php artisan route:clear
```
