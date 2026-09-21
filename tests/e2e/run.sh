#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
SERVER_LOG="${TMPDIR:-/tmp}/izi-guard-e2e-server.log"
DOM_OUT="${TMPDIR:-/tmp}/izi-guard-e2e-dom.html"

BROWSER=""
for candidate in chromium chromium-browser google-chrome google-chrome-stable; do
  if command -v "$candidate" >/dev/null 2>&1; then BROWSER="$candidate"; break; fi
done
if [ -z "$BROWSER" ]; then echo 'SKIP: no supported Chromium/Chrome binary installed'; exit 77; fi

rm -f "$SERVER_LOG" "$DOM_OUT"
node "$ROOT/tests/e2e/server.js" >"$SERVER_LOG" 2>&1 &
PID=$!
trap 'kill "$PID" 2>/dev/null || true' EXIT

ready=0
for _ in {1..50}; do
  if grep -q '^ready$' "$SERVER_LOG" 2>/dev/null; then ready=1; break; fi
  if ! kill -0 "$PID" 2>/dev/null; then echo 'E2E server exited early'; cat "$SERVER_LOG" || true; exit 1; fi
  sleep .1
done
if [ "$ready" -ne 1 ]; then echo 'E2E server did not become ready'; cat "$SERVER_LOG" || true; exit 1; fi

set +e
timeout 20s "$BROWSER" --headless --no-sandbox --disable-gpu --disable-dev-shm-usage \
  --virtual-time-budget=6000 --dump-dom http://127.0.0.1:18765/ >"$DOM_OUT" 2>/dev/null
browser_rc=$?
set -e
if [ "$browser_rc" -ne 0 ]; then echo "SKIP: headless browser unavailable or hung (exit $browser_rc)"; exit 77; fi

if ! grep -q 'data-e2e="passed"' "$DOM_OUT"; then
  echo 'FAIL: browser flow did not reach protected form submission'
  echo '--- DOM tail ---'; tail -n 80 "$DOM_OUT" || true
  echo '--- server log ---'; cat "$SERVER_LOG" || true
  exit 1
fi
if ! grep -q 'data-token="gt_e2e_token"' "$DOM_OUT"; then
  echo 'FAIL: Guard token was not injected into the protected form'
  echo '--- DOM tail ---'; tail -n 80 "$DOM_OUT" || true
  echo '--- server log ---'; cat "$SERVER_LOG" || true
  exit 1
fi

echo 'Browser E2E passed.'
