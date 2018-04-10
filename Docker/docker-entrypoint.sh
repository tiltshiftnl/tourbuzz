#!/usr/bin/env bash

echo Starting server

set -u
set -e

service php7.0-fpm start
nginx -g "daemon off;"