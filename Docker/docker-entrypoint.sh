#!/usr/bin/env bash

echo Starting server

set -u
set -e

tail -f /var/log/php7.0-fpm.log &

service php7.0-fpm start
nginx -g "daemon off;"
