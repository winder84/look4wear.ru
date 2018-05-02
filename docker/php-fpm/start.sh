rm -rf /app/var/cache/* /app/var/logs/*
chown -R www-data:www-data /app/var/cache /app/var/logs
chmod -R 777 /app/var/cache /app/var/logs
