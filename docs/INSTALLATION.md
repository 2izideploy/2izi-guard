# Installation

This guide describes a fresh 2IZI Guard 0.4.10 installation for a PHP application.

## 1. Place the code

Prefer keeping the Guard core outside the public document root and exposing only the `public/` endpoints/assets through your application or web-server routing. If the complete Guard directory is under a document root, explicitly deny direct web access to `config/`, `src/`, `database/`, `tests/`, `storage/`, and administrative files.

## 2. Create the configuration

Copy:

```bash
cp config/guard.example.php config/guard.php
```

`config/guard.php` is intentionally gitignored. Do not commit it.

Configure:

- canonical HTTPS `origin`;
- database DSN/user/password;
- trusted proxies, if any;
- rate-limit backend;
- allowed actions and policies;
- server-only application context / assurances.

Generate independent 256-bit keys:

```bash
php bin/generate-key.php   # HMAC
php bin/generate-key.php   # privacy
php bin/generate-key.php   # rate_limit
```

Insert each generated value into the matching key ring in `config/guard.php`. Do not reuse an application password or database password as a Guard key.

## 3. Install the database schema

Import `database/install.sql` into the dedicated application database. Existing installations should use the migration tooling appropriate to the starting version instead of blindly re-importing the install script.

Then run:

```bash
php bin/migrate.php
php bin/diagnose.php
```

Diagnostics should report all required checks as OK before enforcement is enabled.

## 4. Load the browser client

```html
<script
  src="/guard/public/assets/guard.js?v=0.4.10"
  data-guard-base="/guard/public"
  data-guard-css="/guard/public/assets/guard.css?v=0.4.10"
  data-guard-worker="/guard/public/assets/guard-worker.js?v=0.4.10"
  data-guard-isolation="shadow"
  defer></script>
```

Mark a form with a registered action:

```html
<form method="post" data-guard-action="contact">
  <!-- existing fields -->
  <div data-guard-ui-slot></div>
  <div data-guard-badge-slot></div>
</form>
```

## 5. Protect the server action

The server chooses the expected action. Never authorize using an arbitrary client-provided action name.

```php
/** @var TwoIzi\Guard\Core\Guard $guard */
$guard = require '/path/to/guard/bootstrap.php';

$result = $guard->verifyAndConsume(
    $_POST['guard_token'] ?? '',
    'contact'
);

if (!$result->allowed()) {
    http_response_code(403);
    exit;
}

// Run the protected business operation only after Guard allows it.
```

## 6. Roll out safely

Start with `shadow`, inspect predicted decisions and false positives, then tune the action policy and move selected actions to `adaptive`/`invisible` enforcement.

Critical actions should normally use `fail_mode => closed` and should step up to authenticated controls (for example passkeys/WebAuthn or MFA) when the business risk requires it.
