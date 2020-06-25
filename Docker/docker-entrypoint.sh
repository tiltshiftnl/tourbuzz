#!/usr/bin/env bash

echo Starting server

set -u
set -e

cat > /srv/web/tourbuzz/tourbuzz/.env <<EOF
GOOGLEMAPS_API_KEY="${GOOGLEMAPS_API_KEY}"
EOF


tail -f /var/log/php7.0-fpm.log &

touch /srv/web/tourbuzz/tourbuzz/logs/app.log
chown -R www-data:www-data /srv/web/tourbuzz/tourbuzz/logs
tail -f /srv/web/tourbuzz/tourbuzz/logs/app.log &

/etc/init.d/php7.0-fpm start && nginx -g "daemon off;"
