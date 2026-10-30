#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
find app bin public tests -name '*.php' -print0 | xargs -0 -n1 php -l >/dev/null
php bin/check-lang.php
php tests/map-template.php
php tests/contacts.php
php tests/integrations.php
for kind in sample aggregator settings photo; do
  export ENOTIS_DATA=$(mktemp -d)
  export ENOTIS_MAIL_FILE="$ENOTIS_DATA/mail.txt"
  export ENOTIS_PHOTO_FAKE=1
  fixture=aggregator; [ "$kind" != sample ] || fixture=sample
  php bin/import.php "tests/fixtures/$fixture/site.json"
  php -S 127.0.0.1:8081 -t public public/index.php >"$ENOTIS_DATA/server.log" 2>&1 &
  pid=$!
  trap 'kill "$pid" 2>/dev/null || true' EXIT
  sleep 1
  case "$kind" in
    sample) tests/admin-smoke.sh http://127.0.0.1:8081; python3 tests/contacts-http.py http://127.0.0.1:8081 ;;
    aggregator) tests/tabs-smoke.sh http://127.0.0.1:8081 ;;
    settings) tests/settings-smoke.sh http://127.0.0.1:8081 "$ENOTIS_MAIL_FILE" ;;
    photo) tests/photo-smoke.sh http://127.0.0.1:8081 ;;
  esac
  kill "$pid"; wait "$pid" 2>/dev/null || true
  if grep -E 'PHP (Warning|Notice|Deprecated|Fatal)' "$ENOTIS_DATA/server.log" | grep -v JIT; then exit 1; fi
 done
tests/update-smoke.sh 8085 8086
php bin/build-release.php /tmp/enotis-1-dist
tests/package-smoke.sh /tmp/enotis-1-dist/enotis-admin-1-2.0.0.zip
printf 'All checks passed\n'
