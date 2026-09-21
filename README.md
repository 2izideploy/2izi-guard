# 2IZI Guard

**Self-hosted adaptive anti-bot and abuse protection for PHP applications.**

2IZI Guard protects **server-side application actions**, not only a visible CAPTCHA widget. The core can evaluate request context locally, apply rate limits and adaptive friction, issue a short-lived one-time authorization token, and require the protected endpoint to consume that token before the business operation runs.

> **Status:** `0.4.10` · pre-1.0 · active development. Start in **Shadow Mode**, observe real traffic, tune policies, then enable calibrated enforcement.

- Website: https://2iziguard.com/
- Documentation: https://2iziguard.com/docs/
- Security contact: security@2iziguard.com
- Threat model: [`SECURITY.md`](SECURITY.md)

## Why Guard

- **Self-hosted core runtime** — no mandatory Google, Yandex, Cloudflare, external CAPTCHA API, CDN, or third-party telemetry.
- **Server-side authority** — JavaScript success is never authorization.
- **Adaptive friction** — trusted traffic can pass silently; higher risk can trigger Proof-of-Work, interaction, throttling, or denial.
- **Action-aware policies** — login, registration, password reset, contact forms, checkout, uploads, and custom actions can have different limits and thresholds.
- **Replay-resistant tokens** — random 256-bit opaque tokens, short TTL, action/session/origin binding, hash-only token storage, and atomic one-time consumption.
- **Explainable risk decisions** — internal reason codes and Shadow Mode predictions support tuning without exposing scoring to the requester.
- **Privacy-first defaults** — IP and browser signals are not treated as identity; invasive fingerprinting is not required.
- **Responsive localized UI** — Shadow DOM isolation, touch/keyboard support, RTL-ready layout, and extensible locale packs.

## Security model

Assume the attacker can read the entire repository, JavaScript, API contract, database schema, PoW algorithm, and configured thresholds. Security must **not** depend on obscurity.

The protected boundary is server-side. Guard assumes the attacker does **not** possess server cryptographic keys, another user's active first-party session secret, host-application authentication secrets, or arbitrary code execution on the application server.

Read [`SECURITY.md`](SECURITY.md) before production use.

## Requirements

- PHP 8.1+
- MariaDB / MySQL baseline storage
- PDO
- Optional Redis for rate limiting
- Modern browser for the visitor UI

## Quick start

See [`docs/INSTALLATION.md`](docs/INSTALLATION.md) for the complete installation flow.

Frontend:

```html
<script src="/guard/public/assets/guard.js?v=0.4.10" defer></script>

<form method="post" data-guard-action="contact">
    <!-- fields -->
</form>
```

Protected action:

```php
$result = Guard::verifyAndConsume(
    $_POST['guard_token'] ?? '',
    'contact'
);

if (!$result->allowed()) {
    http_response_code(403);
    exit;
}
```

The `action` used for authorization must be selected by server code, not trusted from arbitrary browser input.

## Modes

- **Shadow** — observe and predict without enforcement.
- **Invisible** — use non-visible controls without an interactive challenge.
- **Adaptive** — choose PASS / PoW / interaction / deny according to policy and risk.
- **Disabled** — explicit administrative disablement.

Critical actions should normally be `fail_closed`. Guard does not replace authentication, authorization, CSRF protection, MFA/passkeys, transaction authorization, WAF/DDoS controls, or business limits.

## Tests

```bash
bash tests/run-all.sh
```

The suite covers cryptographic smoke tests, replay/tampering checks, known-source adversarial assumptions, risk behavior, trusted-proxy parsing, fallback behavior, localization/UI contracts, responsive/Shadow DOM isolation, and optional MySQL/MariaDB replay/concurrency integration tests.

For the database integration test, use a **disposable database only**:

```bash
export IZI_GUARD_TEST_DSN='mysql:host=127.0.0.1;dbname=izi_guard_test;charset=utf8mb4'
export IZI_GUARD_TEST_USER='root'
export IZI_GUARD_TEST_PASSWORD='...'
php tests/integration-mysql.php
```

## Documentation

- [Installation](docs/INSTALLATION.md)
- [Configuration](docs/CONFIGURATION.md)
- [Architecture](docs/ARCHITECTURE.md)
- [Testing and release checks](docs/TESTING.md)
- [Open-source security model](docs/OPEN-SOURCE-SECURITY.md)
- [Responsible disclosure](docs/RESPONSIBLE-DISCLOSURE.md)
- [Russian integration notes](docs/ru/INTEGRATION.md)
- [Changelog](CHANGELOG.md)

The multilingual documentation website is maintained at https://2iziguard.com/docs/.

## Public repository hygiene

Never commit production `config/guard.php`, `.env`, APP_KEY material, HMAC/privacy/rate-limit keys, database dumps, real security events, cookies, sessions, or Guard tokens. The example configuration contains placeholders only.

## Contributing

See [`CONTRIBUTING.md`](CONTRIBUTING.md). Report exploitable security issues privately using [`SECURITY.md`](SECURITY.md), not a public issue.

## License

2IZI Guard is licensed under the **Apache License, Version 2.0** (`Apache-2.0`). See [`LICENSE`](LICENSE) and [`NOTICE`](NOTICE).

You may use, modify, and distribute the software, including in commercial products, subject to the terms of Apache-2.0. Production secrets and deployment-specific credentials are not part of the licensed source distribution and must never be committed to the repository.
