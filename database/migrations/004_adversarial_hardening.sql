ALTER TABLE guard_challenges
    ADD COLUMN issued_at_ms BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER expires_at,
    ADD COLUMN expires_at_ms BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER issued_at_ms,
    ADD COLUMN integrity_key_id VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NULL AFTER interactive_min_ms,
    ADD COLUMN integrity_mac BINARY(32) NULL AFTER integrity_key_id,
    ADD COLUMN state_key_id VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NULL AFTER integrity_mac,
    ADD COLUMN state_mac BINARY(32) NULL AFTER state_key_id;

ALTER TABLE guard_tokens
    ADD COLUMN origin_key_id VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NULL AFTER origin_hash,
    ADD COLUMN issued_at_ms BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER expires_at,
    ADD COLUMN expires_at_ms BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER issued_at_ms,
    ADD COLUMN integrity_key_id VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NULL AFTER status,
    ADD COLUMN integrity_mac BINARY(32) NULL AFTER integrity_key_id,
    ADD COLUMN state_key_id VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NULL AFTER integrity_mac,
    ADD COLUMN state_mac BINARY(32) NULL AFTER state_key_id;

CREATE TABLE IF NOT EXISTS guard_submissions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    submission_hash BINARY(32) NOT NULL,
    session_hash BINARY(32) NOT NULL,
    action VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    token_hash BINARY(32) NOT NULL,
    result VARCHAR(16) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    created_at DATETIME(6) NOT NULL,
    expires_at DATETIME(6) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_guard_submissions_hash (submission_hash),
    KEY ix_guard_submissions_expires (expires_at),
    KEY ix_guard_submissions_session_action (session_hash, action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
