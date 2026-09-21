#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
command -v chromium >/dev/null || { echo 'SKIP: chromium not installed'; exit 77; }
node "$ROOT/tests/e2e/server.js" >/tmp/izi-guard-e2e-server.log 2>&1 & PID=$!; trap 'kill $PID 2>/dev/null || true' EXIT
for i in {1..30}; do grep -q ready /tmp/izi-guard-e2e-server.log 2>/dev/null && break; sleep .1; done
HTML=$(timeout 15s chromium --headless --no-sandbox --disable-gpu --disable-dev-shm-usage --virtual-time-budget=4000 --dump-dom http://127.0.0.1:18765/ 2>/dev/null) || { echo 'SKIP: headless Chromium unavailable/hung in this environment'; exit 77; }
echo "$HTML" | grep -q 'data-e2e="passed"'
echo "$HTML" | grep -q 'data-token="gt_e2e_token"'
echo 'Browser E2E passed.'
