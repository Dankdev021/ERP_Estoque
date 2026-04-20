#!/bin/sh
set -e
cd /var/www/html

if [ ! -f .env ]; then
  cp .env.example .env
fi

composer install --no-interaction --prefer-dist

if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
  php artisan key:generate --force --no-interaction
fi

mkdir -p storage/logs storage/framework/sessions storage/framework/views storage/framework/cache/data bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true

i=0
while [ "$i" -lt 45 ]; do
  if php -r 'try { new PDO("mysql:host=".(getenv("DB_HOST")?:"mysql").";port=".(getenv("DB_PORT")?:"3306"), getenv("DB_USERNAME"), getenv("DB_PASSWORD")); exit(0);} catch (Throwable $e) { exit(1);}' 2>/dev/null; then
    break
  fi
  i=$((i+1))
  sleep 2
done

php artisan migrate --force --no-interaction

exec "$@"
