#!/bin/sh
# Runs every time the container starts, before Apache.
set -e
cd /var/www/html

# 1. Create the table. The database may still be starting, so try a few times.
tries=0
until php bin/init-db.php; do
    tries=$((tries + 1))
    if [ "$tries" -ge 30 ]; then
        echo "Database is not reachable, giving up."
        exit 1
    fi
    echo "Waiting for the database..."
    sleep 2
done

# 2. PHP-FPM runs as www-data and must be able to write uploads and sessions.
# (storage/uploads is a folder of your computer; on Windows chown may be refused there, which is fine.)
mkdir -p storage/uploads storage/sessions
chown -R www-data:www-data storage 2>/dev/null || true

# 3. Start PHP-FPM (the CMD from the Dockerfile).
exec "$@"
