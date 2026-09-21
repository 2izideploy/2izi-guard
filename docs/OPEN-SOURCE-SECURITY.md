# Why source visibility is compatible with 2IZI Guard security

2IZI Guard is designed under a Kerckhoffs-style assumption: an attacker may know the source code, JavaScript, database schema, API contract, challenge algorithm and configured thresholds.

Therefore the project MUST NOT depend on:

- minification or obfuscation;
- secret endpoint names;
- hidden field names;
- a private challenge algorithm;
- users being unable to understand the implementation.

The secrets are server-side cryptographic keys and active host-application authentication/session secrets, not the algorithm itself.

Publishing source can improve reviewability and trust, but it does not make the project automatically secure. Repository access, reviews, tests, disclosure handling, release integrity and secret hygiene remain mandatory.
