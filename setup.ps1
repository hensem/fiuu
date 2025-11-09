# ===============================================
# 🧩 Laravel + Docker Full Setup Script (Windows)
# Author: You 😎
# ===============================================

Write-Host "🚀 Starting Laravel Docker setup..." -ForegroundColor Cyan

# --- Step 1: Move into docker folder ---
Set-Location "$PSScriptRoot\docker"

# --- Step 2: Build and start Docker containers ---
Write-Host "🐳 Building Docker containers..."
docker compose build

Write-Host "🔧 Starting containers..."
docker compose up -d

# --- Step 3: Wait for MySQL to be ready ---
Write-Host "⏳ Waiting for MySQL to be ready (10s)..."
Start-Sleep -Seconds 10

# --- Step 4: Restore database from dump if available ---
$dbDump = "$PSScriptRoot\mysql_dump.sql"
if (Test-Path $dbDump) {
    Write-Host "💾 Restoring database from mysql_dump.sql..."
    docker exec -i laravel_db mysql -u root -proot laravel < $dbDump
} else {
    Write-Host "⚠️ No mysql_dump.sql found. Skipping database restore." -ForegroundColor Yellow
}

# --- Step 5: Install backend dependencies ---
Write-Host "📦 Installing Composer dependencies..."
docker exec -it laravel_app bash -c "cd /var/www/html && composer install"

# --- Step 6: Generate app key & clear config ---
Write-Host "🔑 Generating Laravel app key..."
docker exec -it laravel_app php artisan key:generate

Write-Host "🧹 Clearing Laravel caches..."
docker exec -it laravel_app php artisan config:clear
docker exec -it laravel_app php artisan cache:clear
docker exec -it laravel_app php artisan route:clear
docker exec -it laravel_app php artisan view:clear

# --- Step 7: Install and build frontend assets ---
Write-Host "🧱 Installing Node dependencies..."
docker exec -it laravel_app bash -c "cd /var/www/html && npm install"

Write-Host "⚡ Building Vite assets..."
docker exec -it laravel_app bash -c "cd /var/www/html && npm run build"

# --- Step 8: Fix permissions (optional, safe on Linux) ---
Write-Host "🔒 Fixing storage/cache permissions..."
docker exec -it laravel_app bash -c "chown -R www-data:www-data storage bootstrap/cache || true"
docker exec -it laravel_app bash -c "chmod -R 775 storage bootstrap/cache || true"

# --- Step 9: Done! ---
Write-Host ""
Write-Host "✅ Laravel Docker setup complete!" -ForegroundColor Green
Write-Host "🌍 Visit: http://localhost:8080"
Write-Host ""
