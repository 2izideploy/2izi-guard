# Changelog

## 0.4.10

Responsive Layout Hotfix.

- Fixed challenge preview collapsing into a narrow vertical strip in 320/390 px flex-centered preview containers.
- `IZIGuard.preview()` now explicitly normalizes its synthetic form with block layout, `width:100%`, `min-width:0`, `min-inline-size:0`, flex/grid-safe sizing and zero margins/padding/borders.
- Guard surface hosts now reset min inline size and use flex-safe challenge sizing (`flex: 0 1 420px`).
- Admin preview mount now uses grid centering instead of shrink-to-fit flex centering.
- Admin preview adds an explicit form sizing contract so host CSS cannot shrink the synthetic form under normal embedding.
- Added `responsive-layout-hotfix` regression coverage.
- No database, challenge/token protocol, key, risk-engine or server authorization changes.

## 0.4.9

Isolation diagnostic hotfix.

- Fixed the false-negative CSS-isolation diagnostic where `sans-serif` was incorrectly matched as `serif`.
- Isolation diagnostics wait for the actual Shadow DOM `guard.css` load/error result instead of a fixed delay.
- Added precise diagnostic reasons for missing Shadow root, missing/failed stylesheet, width changes, touch-target changes and font leakage.
- No production security-protocol changes.

## 0.4.8

Preview Redesign / embedding polish release with no database/schema/security-protocol migration.

- Rebuilt the admin preview around one visible Guard state at a time instead of a four-card state grid.
- Preview now has three independent controls: container width, input modality and Guard state.
- Added a centered real device/container canvas for 960 / 720 / 390 / 320 px preview widths.
- Removed the visible Hostile CSS mode from the main preview; CSS isolation is now tested separately through a dedicated diagnostic action.
- Added a hidden adversarial CSS fixture that validates Shadow DOM isolation without intentionally distorting the preview UI.
- Moved `2IZI Guard` branding in the interactive challenge from the title row to a small secondary footer line, so long translated titles never compete with the product name.
- Challenge header now contains only the shield and the localized security-check title.
- Checking/verified phases continue to hide branding and collapse to one short state.
- Added `preview-redesign` regression coverage and updated responsive-isolation/hostile-CSS tests.
- Server-side challenge/token/risk/origin/rate-limit enforcement is unchanged.

## 0.4.7

Responsive Isolation release with no database/schema/security-protocol migration.

- Decoupled preview viewport width from input modality. Preview now tests 960/720/390/320 px independently from mouse/touch/keyboard.
- Added Shadow DOM rendering as the default visitor-UI isolation mode (`data-guard-isolation="shadow"`).
- Added `scoped` fallback mode for compatibility.
- Added isolated `izi-guard-surface` hosts for badge, status and interactive challenge.
- Added component-local stylesheet loading inside Shadow roots.
- Added component reset (`all: initial`) and containment to reduce accidental host-style inheritance.
- Interactive challenge is now `width:100%` with `max-width:420px`; it no longer expands across large host forms.
- Existing container-query regular/compact/micro behavior remains based on actual Guard width.
- Added admin hostile-CSS preview to intentionally stress embedding isolation.
- Updated browser E2E harness to locate the hold control inside Shadow DOM.
- Added responsive-isolation and hostile-CSS release contract tests.
- Shadow DOM is explicitly documented as UI isolation only, not a security boundary.
- Server-side challenge/token/risk/origin/rate-limit enforcement is unchanged.

## 0.4.6

Minimal visitor UI release with no database/schema/security-protocol migration.

- Removed repeated `2IZI Guard` branding from checking and verified states.
- Normal protected badge now separates the short state label from secondary product branding.
- Replaced the previous filled success mark with a plain green check glyph.
- Checking is now a single short state such as `Checking… / Проверяем…`.
- Verified is now a single short state such as `Verified / Проверено`.
- Elevated-risk challenge keeps one title, one secondary brand label and one hold action only.
- After hold completion, the hold control disappears and the challenge collapses to one `Checking…` state while server verification runs.
- Preview simulates the same compact checking → verified transition instead of showing duplicated success in the header and button.
- Reduced challenge padding/touch surface visual weight while preserving a 48 px minimum touch target.
- Added short multilingual state copy for EN/RU/zh-CN/JA/IT.
- Security authority remains fully server-side.

## 0.4.5

Compact multilingual challenge UI release with no database/schema/security-protocol migration.

- Replaced the elevated-risk stack of challenge badge + repeated title + local note + instruction + action with one compact challenge card.
- The normal trust badge is hidden while the visible interactive challenge is mounted, eliminating duplicate status UI.
- Visitor title is now neutral `Security check / Проверка безопасности` rather than telling the user they are “high risk”.
- Moved the long interaction instruction to screen-reader-only text; the visible action itself communicates the interaction.
- Added normal and compact hold labels for pointer, touch and keyboard modalities in EN/RU/zh-CN/JA/IT.
- Added container-query responsive densities so the same component adapts to normal forms, narrow mobile cards and micro-width containers.
- Removed ellipsis as the primary hold-label fitting strategy; micro layout switches to an explicit compact localized label.
- Converted challenge positioning/padding to logical CSS properties for RTL-friendly layout.
- Added `data-guard-locale` and `IZIGuard.registerLocale()` for explicit/custom locale integration.
- Preserved Pointer Events, keyboard support, 52 px touch target, progress indication and reduced-motion behavior.
- Added responsive multilingual regression tests.
- Security authority remains entirely server-side.

## 0.4.4

Visitor challenge UI polish release with no database/schema/security-protocol migration.

- Shortened desktop copy so the primary hold action stays on one line in normal form widths.
- Russian desktop button is now “Удерживайте для проверки”; touch keeps “Коснитесь и удерживайте”.
- Replaced visitor-facing “no external CAPTCHA” implementation wording with the cleaner “Local protection · 2IZI Guard” equivalent in all bundled locales.
- Decoupled challenge typography from host-project root font scaling by using bounded component pixel sizes.
- Reduced card spacing and visual weight while preserving a 56 px touch target.
- Hold control now uses a two-column layout for label + percentage; label is single-line with safe ellipsis only at extreme widths.
- Added narrow-screen adjustments down to 340 px without reducing the touch target.
- Added a UI-polish release-contract test.
- Security authority remains fully server-side; no authorization behavior changed.

## 0.4.3

Interactive preview hotfix with no database/schema/security-protocol migration.

- Fixed the elevated-risk admin preview being a static hard-coded `48%` sample.
- `IZIGuard.preview()` now runs a real local hold interaction from 0→100% using the same Pointer Events/keyboard UX contract as the visitor challenge.
- Preview completion switches the local badge/button to verified and automatically resets after a configurable delay.
- Added `holdMs` and `resetAfterMs` preview options.
- Explicit preview `modality` now remains stable while testing Desktop/Touch/Keyboard from another input device.
- The admin preview now calls `IZIGuard.preview()` instead of rendering a fake 48% button.
- Preview remains simulation-only: it does not call challenge/verify endpoints, issue a token, consume rate limits, or write a security event.
- Added an interactive-preview release-contract test to prevent the static-progress regression.

## 0.4.2

Mobile interaction and touch UX release with no database/schema/security-protocol migration.

- Added touch-aware copy: phones/tablets show “Touch and hold” / “Коснитесь и удерживайте”; mouse and keyboard keep modality-specific instructions.
- Reworked hold interaction on Pointer Events with pointer capture for mouse, touch and pen.
- Added smooth requestAnimationFrame progress plus visible percentage.
- Cancels an unfinished hold on pointer cancellation, substantial movement outside the button, lost capture, window blur, element blur or page visibility loss.
- Suppresses long-press context menu, text selection, tap highlight and drag behavior on the Guard hold control.
- Enlarged mobile touch target and spacing for narrow screens.
- Added keyboard-specific instructions while preserving Space/Enter hold support.
- `IZIGuard.preview()` now accepts `modality: touch|pointer|keyboard`; admin preview has Desktop/Touch views.
- Added EN/RU/zh-CN/JA/IT touch and keyboard localization keys.
- Added mobile interaction release-contract test.
- No authorization decision depends on pointer/touch events; server-side action/token/risk enforcement is unchanged.

## 0.4.1

UI/UX release with no Guard database schema change.

- Added compact visitor-facing 2IZI Guard badge, visible by default on protected forms.
- Added `always`, `during`, and `hidden` badge modes.
- Added explicit `data-guard-badge-slot` and `data-guard-ui-slot` mount points.
- Added protected/checking/verified/challenge/error visual states.
- Redesigned adaptive interactive fallback with shield branding, local-protection note, accessible hold interaction and solid-color UI.
- Added localized visitor UI for EN/RU/zh-CN/JA/IT before the first API request; server locale bundle still remains authoritative after challenge issuance.
- Added `IZIGuard.preview()` for host-project admin previews.
- Added authenticated include-only `admin/preview.php` and redesigned Guard overview dashboard.
- Added `INTEGRATION-RU.md` with production integration instructions and a host-application example.
- Added UI locale contract test.
- Diagnostics now reads the version from the `VERSION` file instead of a hard-coded string.
- No security authorization decision depends on the new visual layer.

## 0.4.0

- Adopted an explicit attacker-knows-source security model; added `SECURITY.md` and adversarial release tests.
- Added versioned HMAC/privacy/rate-limit key rings with `active`, `verify_only` and `retired` states plus key IDs.
- Added `bin/rotate-key.php` helper; Guard never rewrites secret configuration automatically.
- Added HMAC integrity for immutable challenge fields, including signed millisecond issue/expiry bounds.
- Added independent challenge state MAC and state-MAC-aware transitions for attempts/failure/token issuance.
- Added token immutable-field integrity MAC plus independent token state MAC while preserving one-winner atomic consume semantics.
- Added origin key IDs so short-lived tokens can survive controlled privacy-key rotation grace periods.
- Separated the rate-limit key from the privacy key to avoid routine privacy-key rotation silently resetting buckets.
- Replaced interactive elapsed-time calculation with signed `issued_at_ms`, preventing DB timestamp tampering from satisfying the minimum interaction delay.
- Added actual Origin validation at the protected business action and in degraded mode.
- Added dedicated global/network consume rate limits before token lookup to make direct random-token POST flooding cheap to reject.
- Added server-only `assurances` and `required_assurances` for critical actions (e.g. verified account, MFA, WebAuthn/passkey). Required assurances are re-checked at final consume.
- Added random `guard_submission_id` injection and persistent submission records. Replayed submissions are deny-by-default and identifiable via `GuardResult::replayed()`.
- Added explicit degraded execution layer and `safe-bootstrap.php`. Only recognized DB/Redis availability failures can enter degraded mode; critical actions remain fail-closed and low-risk fail-open requires explicit `emergency.enabled` plus per-action `open_with_limit`.
- Added local emergency limiter with Origin validation and per-submission replay protection.
- Added recent event statistics, Shadow tuning advisor, read-only effective-rule view and an admin overview. All admin pages require the host-authenticated `IZI_GUARD_ADMIN_AUTHORIZED` gate.
- Added migration `004_adversarial_hardening.sql`, submission table and schema diagnostics for all new integrity columns.
- Added known-source adversarial tests, server-context assurance tests and fallback security tests.

## 0.3.0

- Added server-only account/application signals and account-scoped velocity limits.
- Added adaptive `pass / pow / interactive / deny` prediction and zero-friction Shadow Mode.
- Added accessible PoW + hold fallback, optional Redis token bucket, migrations and browser/E2E harness.

## 0.2.0

- Explainable Risk Engine, adaptive PoW, dynamic honeypot, verify rate limits, proxy-chain hardening, IPv6 /64 network keys, diagnostics and cleanup.

## 0.1.0

- Initial self-hosted challenge/token core with session/action/origin binding and atomic one-time token consumption.
