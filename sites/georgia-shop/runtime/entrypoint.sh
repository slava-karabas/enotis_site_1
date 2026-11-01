#!/bin/sh
set -eu
cd /var/www/html
mkdir -p "$ENOTIS_DATA" public/uploads
if [ ! -f "$ENOTIS_DATA/site.sqlite" ]; then
    php bin/import.php /seed/site.json /seed/photos
    php bin/seed-georgia-shop.php
    if [ "${HENOTIS_DEMO_CHECKOUT:-0}" = "1" ]; then php bin/seed-georgia-demo.php; fi
fi
exec php -S 0.0.0.0:8080 -t public public/index.php

