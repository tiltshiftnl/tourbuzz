#!/usr/bin/env bash

echo Starting server

set -u
set -e

cat > /srv/web/tourbuzz/tourbuzz/.env <<EOF
GOOGLEMAPS_API_KEY="${GOOGLEMAPS_API_KEY}"

TOURBUZZ_URI_PROTOCOL="${TOURBUZZ_URI_PROTOCOL}"
TOURBUZZ_URI="${TOURBUZZ_URI}"
TOURBUZZ_API_URI_PROTOCOL="${TOURBUZZ_API_URI_PROTOCOL}"
TOURBUZZ_API_URI="${TOURBUZZ_API_URI}"
TOURBUZZ_RECIPIENTS="${TOURBUZZ_RECIPIENTS}"
TOURINGCAR_PROTOCOL="${TOURINGCAR_PROTOCOL}"
TOURINGCAR_URI="${TOURINGCAR_URI}"
TOURINGCAR_CONTACT_NAME="${TOURINGCAR_CONTACT_NAME}"
TOURINGCAR_CONTACT_EMAIL="${TOURINGCAR_CONTACT_EMAIL}"
EOF

tail -f /var/log/php7.3-fpm.log &

touch /srv/web/tourbuzz/tourbuzz/logs/app.log
chown -R www-data:www-data /srv/web/tourbuzz/tourbuzz/logs
tail -f /srv/web/tourbuzz/tourbuzz/logs/app.log &

service php7.3-fpm start 
nginx -g "daemon off;"
