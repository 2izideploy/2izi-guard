# 2IZI Guard v0.4.10

First public pre-1.0 source release candidate of the current 2IZI Guard PHP core.

## What it is

2IZI Guard is a self-hosted adaptive anti-bot and abuse-protection layer for server-side application actions. It does not treat a visible CAPTCHA widget as the authorization boundary: the protected action must receive and atomically consume a short-lived one-time Guard token on the server.

## Highlights

- self-hosted core runtime with no mandatory external CAPTCHA provider;
- adaptive PASS / Proof-of-Work / interactive / deny flow;
- 256-bit opaque one-time tokens with hash-only storage;
- action + session + origin binding and short TTL;
- HMAC integrity for challenge/token records and mutable state;
- atomic one-winner token consumption and replay resistance;
- multi-key rate limiting and server-only application/account signals;
- Shadow Mode for tuning before enforcement;
- privacy-first signal model without mandatory invasive fingerprinting;
- responsive localized visitor UI with Shadow DOM isolation;
- red-team/regression tests based on the assumption that the attacker knows the source code.

## Requirements

- PHP 8.1+
- MariaDB / MySQL baseline storage
- PDO
- optional Redis for rate limiting

## Before production

This is pre-1.0 software. Start with Shadow Mode, observe real traffic, tune policies, run the complete test suite, and enable enforcement gradually. 2IZI Guard does not replace authentication, authorization, CSRF protection, MFA/passkeys, transaction authorization, WAF/DDoS controls, or business limits.

## Security

Read `SECURITY.md` before deployment. Do not publish exploitable vulnerabilities in public issues; use `security@2iziguard.com`.

## Integrity

Official 0.4.10 distribution artifact:

```text
b5738e5289f3e7086b39b064a10661ab96330dde8bde7a2e935c28ce195f91e5  2izi-guard-0.4.10-release.zip
```

## License

Apache License 2.0. See `LICENSE` and `NOTICE`.
