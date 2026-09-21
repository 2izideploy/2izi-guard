# Configuration

`config/guard.example.php` is the canonical configuration template. Production configuration belongs in `config/guard.php` or another protected file referenced by `IZI_GUARD_CONFIG`.

## Origin

Set an exact HTTPS origin and keep `require_origin_header` enabled for browser flows unless your deployment has a documented reason to do otherwise.

## Key rings

Guard separates cryptographic purposes:

- `hmac` — integrity for signed Guard state;
- `privacy` — privacy-preserving keyed hashing / origin-related state;
- `rate_limit` — rate-limit key derivation.

Do not reuse keys between purposes. Key rotation is explicit; Guard does not silently rewrite production secrets.

## Trusted proxies

Forwarded headers are ignored unless the direct peer belongs to the configured trusted-proxy list. Configure only infrastructure you actually control.

## Rate limiting

The baseline backend is database storage. Redis is optional. Policies can combine global, network, session, account, and action-specific limits.

## Server context

`server_context_provider` runs on the server. Account identifiers, application risk signals, and assurances must originate from trusted server state — never from browser JSON.

## Action policies

Each protected action is registered in configuration. Typical settings include:

- mode;
- honeypot requirement;
- Proof-of-Work difficulty range / target time;
- interactive minimum hold time;
- risk thresholds;
- rate limits;
- server-only application signals;
- required assurances;
- fail mode / emergency limits.

The configuration values in `guard.example.php` are examples, not universal production thresholds. Use Shadow Mode and real traffic to calibrate them.

## Localization

The core distribution currently includes visitor UI packs for `en`, `ru`, `zh-CN`, `ja`, and `it`. Additional locales can be added without changing the authorization protocol. The public documentation website supports a broader set of site languages; site localization and widget language packs are intentionally separate concerns.
