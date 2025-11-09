#!/bin/bash
echo "🧹 Cleaning and rebuilding Laravel + Vue build..."

# Run inside Docker if available
if docker compose ps >/dev/null 2>&1; then
    echo "🐳 Detected Docker — running inside containers..."
    docker compose exec app bash -c "
        set -e
        echo '🧽 Clearing Laravel caches...'
        php artisan optimize:clear || true
        php artisan config:clear || true
        php artisan route:clear || true
        php artisan view:clear || true
        php artisan cache:clear || true

        echo '💥 Removing bootstrap cache...'
        rm -rf bootstrap/cache/*.php || true

        echo '📦 Rebuilding composer autoload...'
        composer dump-autoload || true

        echo '🧩 Cleaning Node environment...'
        rm -rf node_modules package-lock.json || true
        npm install --legacy-peer-deps || true

        echo '⚙️ Building Vue frontend...'
        npm run build || true

        echo '🚀 Re-optimizing Laravel...'
        php artisan optimize || true

        echo '✅ Fix complete inside Docker app container.'
    "
else
    echo "❌ Docker not detected. Please run inside project or container manually."
fi
