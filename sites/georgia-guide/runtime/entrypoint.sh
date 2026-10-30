#!/bin/sh
set -eu
cd /var/www/html
mkdir -p "$ENOTIS_DATA" public/uploads
if [ ! -f "$ENOTIS_DATA/site.sqlite" ]; then
    php bin/import.php /seed/site.json /seed/photos
fi
exec php -S 0.0.0.0:8080 -t public public/index.php
