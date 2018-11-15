#!/usr/bin/env bash

echo Starting server

set -u
set -e

cat > /srv/web/tourbuzz/tourbuzz/.env <<EOF
GOOGLEMAPS_API_KEY="${GOOGLEMAPS_API_KEY}"
EOF


tail -f /var/log/php7.0-fpm.log &

service php7.0-fpm start
nginx -g "daemon off;"
