# 2IZI Guard architecture

## Core idea

Guard protects a **server action**, not merely a visible CAPTCHA widget.

Typical flow:

```text
Request
  ↓
Origin / action / session validation
  ↓
Multi-key rate limits
  ↓
Risk Engine + application context
  ↓
PASS / Proof-of-Work / interactive step / deny
  ↓
256-bit opaque one-time Guard token
  ↓
Protected action
  ↓
Atomic server-side consume
```

## Trust boundaries

- Browser JavaScript is untrusted.
- Shadow DOM is UI isolation, not a security boundary.
- Client IP or a device fingerprint is not identity.
- Application/business signals are accepted only from the host server.
- Authorization is granted only by server-side token verification and consume.

## Token properties

The current design uses random opaque capabilities rather than client-readable authorization JWTs. Tokens are short-lived, bound to session + action + origin, stored only by hash, and consumed once.

See `SECURITY.md` for the formal threat model and limitations.
