#!/usr/bin/env bash
set -euo pipefail
Z=${1:?ZIP}; W=$(mktemp -d)
php -r '$z=new ZipArchive;$z->open($argv[1]);$z->extractTo($argv[2]);$z->close();' "$Z" "$W"
test ! -e "$W/demo"; test ! -e "$W/docs"; test ! -e "$W/tests"

ENOTIS_DATA="$W/data" php -S 127.0.0.1:8087 -t "$W/public" "$W/public/index.php" >"$W/server.log" 2>&1 &
pid=$!; trap 'kill "$pid" 2>/dev/null || true; rm -rf "$W"' EXIT
sleep 1
curl -fsS http://127.0.0.1:8087/health | grep -q 'engine=2.0.0 schema=5'
J="$W/cookies"; B=http://127.0.0.1:8087
curl -fsSL -c "$J" "$B/install" > "$W/install.html"
T=$(grep -o 'name="csrf" value="[^"]*"' "$W/install.html" | head -1 | sed 's/.*value="//;s/"//')
curl -fsS -b "$J" -c "$J" -o /dev/null -d "csrf=$T&site_title=Чистая установка&site_lang=ru&ui_lang=ru&login=admin&email=test@example.com&password=correct-horse&password2=correct-horse" "$B/admin/install"
test -f "$W/data/config.php"
curl -fsS -b "$J" "$B/admin/api/site" | grep -q 'enotis-feed-free-v2'
curl -fsS "$B/" > /dev/null
if grep -E 'PHP (Warning|Notice|Deprecated|Fatal)' "$W/server.log" | grep -v JIT; then exit 1; fi
echo 'package-smoke: ok'
