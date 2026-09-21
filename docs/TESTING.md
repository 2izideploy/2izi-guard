# Testing and release checks

Run the complete repository test entry point:

```bash
bash tests/run-all.sh
```

The suite covers:

- cryptographic/token smoke checks;
- Proof-of-Work vectors;
- trusted-proxy spoofing behavior;
- Risk Engine behavior;
- known-source adversarial tampering;
- server-only context / assurance handling;
- degraded fallback behavior;
- UI locale contracts;
- touch/mobile interaction contracts;
- responsive/multilingual layouts;
- Shadow DOM / hostile-CSS isolation contracts;
- preview and responsive-layout regressions;
- optional MySQL/MariaDB replay and concurrency integration tests;
- optional browser E2E harness.

## Disposable MySQL/MariaDB test

Never point the integration test at production data.

```bash
export IZI_GUARD_TEST_DSN='mysql:host=127.0.0.1;dbname=izi_guard_test;charset=utf8mb4'
export IZI_GUARD_TEST_USER='root'
export IZI_GUARD_TEST_PASSWORD='...'
php tests/integration-mysql.php
```

A successful concurrency test must produce exactly one winner for simultaneous consumption of the same token.

## Release rule

A release is not accepted only because the happy path works. Security-relevant changes should include a regression test. Review the implementation under the assumption that the attacker knows the complete source tree.
