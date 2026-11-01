#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
find app bin public tests -name '*.php' -print0 | xargs -0 -n1 php -l >/dev/null
php bin/check-lang.php
php tests/map-template.php
php tests/integrations.php
php tests/gift-constructor.php
php tests/catalog-photo.php
php tests/currency.php
python3 tests/shop-schema.py
export ENOTIS_DATA=$(mktemp -d)
export ENOTIS_MAIL_FILE="$ENOTIS_DATA/mail.txt"
php -d upload_max_filesize=64M -d post_max_size=80M -S 127.0.0.1:8081 -t public public/index.php >"$ENOTIS_DATA/server.log" 2>&1 &
pid=$!; trap 'kill "$pid" 2>/dev/null || true' EXIT
sleep 1
python3 tests/shop-admin-smoke.py http://127.0.0.1:8081 --ui
python3 tests/shop-checkout-smoke.py http://127.0.0.1:8081
python3 tests/gift-http.py http://127.0.0.1:8081
python3 tests/product-cards-http.py http://127.0.0.1:8081
if grep -E 'PHP (Warning|Notice|Deprecated|Fatal)' "$ENOTIS_DATA/server.log" | grep -v JIT; then exit 1; fi
printf 'All shop checks passed\n'
