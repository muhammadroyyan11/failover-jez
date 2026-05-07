#!/bin/sh
set -e

echo "=========================================="
echo "  Jezpro Failover Panel - Starting Up"
echo "=========================================="

cd /var/www/html

# Generate APP_KEY jika belum ada
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
    echo "[entrypoint] Generating APP_KEY..."
    php artisan key:generate --force
fi

# Tunggu MySQL siap
echo "[entrypoint] Waiting for MySQL..."
MAX_TRIES=30
COUNT=0
until php -r "
    try {
        \$pdo = new PDO(
            'mysql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT') . ';dbname=' . getenv('DB_DATABASE'),
            getenv('DB_USERNAME'),
            getenv('DB_PASSWORD')
        );
        echo 'ok';
    } catch (Exception \$e) {
        exit(1);
    }
" 2>/dev/null | grep -q ok; do
    COUNT=$((COUNT + 1))
    if [ $COUNT -ge $MAX_TRIES ]; then
        echo "[entrypoint] MySQL not ready after ${MAX_TRIES} tries, continuing anyway..."
        break
    fi
    echo "[entrypoint] MySQL not ready yet... ($COUNT/$MAX_TRIES)"
    sleep 2
done
echo "[entrypoint] MySQL is ready!"

# Jalankan migration
echo "[entrypoint] Running migrations..."
php artisan migrate --force --no-interaction

# Jalankan seeder (hanya jika tabel users kosong)
USER_COUNT=$(php artisan tinker --execute="echo App\Models\User::count();" 2>/dev/null | tail -1 || echo "0")
if [ "$USER_COUNT" = "0" ] || [ -z "$USER_COUNT" ]; then
    echo "[entrypoint] Seeding database..."
    php artisan db:seed --force --no-interaction
fi

# Cache config & routes
echo "[entrypoint] Caching config..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

echo "[entrypoint] Starting supervisord..."
echo "=========================================="
echo "  Panel ready at http://localhost:3000"
echo "  Login: admin@jezpro.id / Admin@12345"
echo "=========================================="

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
