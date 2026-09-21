#!/usr/bin/env bash
set -u
cd "$(dirname "$0")/.."
fail=0
while IFS= read -r -d '' f; do php -l "$f" >/dev/null || fail=1; done < <(find . -name '*.php' -type f -print0)
node --check public/assets/guard.js || fail=1
node --check public/assets/guard-pow.js || fail=1
node --check public/assets/guard-worker.js || fail=1
php tests/security-smoke.php || fail=1
php tests/proxy-smoke.php || fail=1
php tests/risk-engine.php || fail=1
node tests/pow-vector.js || fail=1
php tests/adversarial-known-source.php || fail=1
php tests/server-context.php || fail=1
php tests/fallback-security.php || fail=1
php tests/ui-locales.php || fail=1
php tests/mobile-interaction.php || fail=1
php tests/preview-interaction.php || fail=1
php tests/ui-polish.php || fail=1
php tests/responsive-multilingual.php || fail=1
php tests/minimal-ui.php || fail=1
php tests/responsive-isolation.php || fail=1
php tests/hostile-css.php || fail=1
php tests/preview-redesign.php || fail=1
php tests/isolation-diagnostic.php || fail=1
php tests/responsive-layout-hotfix.php || fail=1
php tests/integration-mysql.php || fail=1
bash tests/e2e/run.sh; e=$?; if [ "$e" -ne 0 ] && [ "$e" -ne 77 ]; then fail=1; fi
exit "$fail"
