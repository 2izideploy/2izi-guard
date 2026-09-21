# 2IZI Guard security policy (0.4.10)

## Reporting a vulnerability

Please do **not** open a public GitHub issue for an exploitable security vulnerability. Send a private report to **security@2iziguard.com** and include the affected version, reproduction steps, expected vs actual behavior, and security impact. Do not include third-party personal data or production secrets.

The latest pre-1.0 release is the primary supported target for security fixes. If you are testing an older pre-1.0 build, reproduce on the latest release where possible.

See also: https://2iziguard.com/.well-known/security.txt


## Kerckhoffs-style assumption

Assume the attacker has the complete Guard source tree, JavaScript, database schema, API contract, PoW algorithm, configured thresholds, a modern AI model, Playwright/Selenium/headless Chromium, residential proxies and the ability to inspect their own traffic.

Security MUST NOT depend on source secrecy, minification, obfuscation, hidden field names, undocumented endpoints or an attacker failing to understand the algorithm.

The attacker is **not** assumed to possess server-side secret keys, another user's active first-party session secret, host-application authentication secrets or arbitrary code execution on the application server.

## Mandatory invariants

- Protected actions require a server decision; JavaScript success is never authorization.
- Guard tokens are random 256-bit opaque capabilities and are stored only by hash.
- Tokens are bound to session + action + origin + short TTL and are single-use.
- Challenge security fields and signed wall-clock bounds have HMAC integrity.
- Challenge mutable state has a separate state MAC.
- Token immutable fields and mutable state have independent MACs.
- State transition updates include the prior state MAC, preserving one-winner race semantics.
- Account/application trust signals originate only from the host server.
- Client IP/fingerprint is never identity and no single browser signal grants access.
- Critical actions use fail-closed policies; degraded fail-open is explicit and locally rate-limited.
- An admin page is never public: host authentication plus `IZI_GUARD_ADMIN_AUTHORIZED=true` is required.

## What Guard cannot guarantee

No CAPTCHA or browser-side anti-bot can prove that a requester is biologically human. A controlled browser can execute JavaScript; a solver farm can provide human interaction; an AI agent can imitate many user behaviors. Critical business actions should therefore step up to authenticated controls such as passkeys/WebAuthn, MFA, verified accounts, transaction authorization and business velocity limits.

A fully compromised application host or an attacker able to restore an earlier complete database snapshot plus previously valid MAC values is outside the guarantees of a purely local self-hosted system. Detecting authoritative full rollback requires an independent monotonic/external anchor. Guard's MACs detect arbitrary record tampering but are not claimed to defeat a total host rollback.

## Red-team release rule

A release is not accepted merely because normal flows work. Run `tests/run-all.sh` and the disposable MySQL/MariaDB integration test. Review the system as if the tester knows every implementation detail. Any bypass that relies only on reading the code is a Guard vulnerability.

## UI isolation in 0.4.10

2IZI Guard uses Shadow DOM by default to reduce accidental styling conflicts with host projects.

This is **not a security boundary**. An application owner, injected script, browser extension or attacker with DOM execution can inspect or alter the visual layer. Security must never depend on Shadow DOM, CSS, hidden UI or component encapsulation.

Authorization continues to depend only on server-side challenge/token validation, risk/business policies, origin/action/session binding and one-time token consumption.


## 0.4.10 layout note

The responsive-layout hotfix changes only UI sizing and preview embedding. Flex/grid sizing, Shadow DOM and CSS isolation are not security boundaries; server-side authorization remains unchanged.
