CREATE TABLE IF NOT EXISTS guard_sessions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    session_hash BINARY(32) NOT NULL,
    created_at DATETIME(6) NOT NULL,
    last_seen_at DATETIME(6) NOT NULL,
    trust_score SMALLINT NOT NULL DEFAULT 0,
    successful_actions INT UNSIGNED NOT NULL DEFAULT 0,
    failed_actions INT UNSIGNED NOT NULL DEFAULT 0,
    successful_challenges INT UNSIGNED NOT NULL DEFAULT 0,
    failed_challenges INT UNSIGNED NOT NULL DEFAULT 0,
    pow_ema_ms DECIMAL(10,2) NULL,
    last_pow_difficulty TINYINT UNSIGNED NULL,
    expires_at DATETIME(6) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_guard_sessions_hash (session_hash),
    KEY ix_guard_sessions_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS guard_challenges (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_id VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    session_hash BINARY(32) NOT NULL,
    action VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    challenge_type VARCHAR(24) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    salt VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    difficulty TINYINT UNSIGNED NOT NULL,
    status VARCHAR(24) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    issued_at DATETIME(6) NOT NULL,
    expires_at DATETIME(6) NOT NULL,
    issued_at_ms BIGINT UNSIGNED NOT NULL DEFAULT 0,
    expires_at_ms BIGINT UNSIGNED NOT NULL DEFAULT 0,
    verified_at DATETIME(6) NULL,
    token_issued_at DATETIME(6) NULL,
    risk_score SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    reason_codes JSON NULL,
    honeypot_name VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
    honeypot_required TINYINT(1) NOT NULL DEFAULT 0,
    client_locale VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NULL,
    policy_version INT UNSIGNED NOT NULL DEFAULT 4,
    attempt_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    predicted_decision VARCHAR(24) CHARACTER SET ascii COLLATE ascii_bin NULL,
    interactive_required TINYINT(1) NOT NULL DEFAULT 0,
    interactive_min_ms INT UNSIGNED NOT NULL DEFAULT 0,
    integrity_key_id VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NULL,
    integrity_mac BINARY(32) NULL,
    state_key_id VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NULL,
    state_mac BINARY(32) NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_guard_challenges_public (public_id),
    KEY ix_guard_challenges_session_action (session_hash, action),
    KEY ix_guard_challenges_expires (expires_at),
    KEY ix_guard_challenges_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS guard_tokens (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    token_hash BINARY(32) NOT NULL,
    session_hash BINARY(32) NOT NULL,
    challenge_id BIGINT UNSIGNED NOT NULL,
    action VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    origin_hash BINARY(32) NOT NULL,
    origin_key_id VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NULL,
    risk_level SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    issued_at DATETIME(6) NOT NULL,
    expires_at DATETIME(6) NOT NULL,
    issued_at_ms BIGINT UNSIGNED NOT NULL DEFAULT 0,
    expires_at_ms BIGINT UNSIGNED NOT NULL DEFAULT 0,
    consumed_at DATETIME(6) NULL,
    revoked_at DATETIME(6) NULL,
    status VARCHAR(24) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    integrity_key_id VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NULL,
    integrity_mac BINARY(32) NULL,
    state_key_id VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NULL,
    state_mac BINARY(32) NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_guard_tokens_hash (token_hash),
    KEY ix_guard_tokens_session_action (session_hash, action),
    KEY ix_guard_tokens_expires (expires_at),
    CONSTRAINT fk_guard_tokens_challenge FOREIGN KEY (challenge_id) REFERENCES guard_challenges (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS guard_rate_limits (
    key_hash BINARY(32) NOT NULL,
    tokens DECIMAL(14,6) NOT NULL,
    updated_at DATETIME(6) NOT NULL,
    expires_at DATETIME(6) NOT NULL,
    PRIMARY KEY (key_hash),
    KEY ix_guard_rate_limits_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS guard_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    event_time DATETIME(6) NOT NULL,
    request_id VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    action VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    session_hash_short CHAR(16) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    network_hash BINARY(32) NOT NULL,
    risk_score SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    decision VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    reason_codes JSON NULL,
    latency_ms INT UNSIGNED NOT NULL DEFAULT 0,
    predicted_decision VARCHAR(24) CHARACTER SET ascii COLLATE ascii_bin NULL,
    mode VARCHAR(24) CHARACTER SET ascii COLLATE ascii_bin NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_guard_events_request (request_id),
    KEY ix_guard_events_time (event_time),
    KEY ix_guard_events_action_time (action, event_time),
    KEY ix_guard_events_decision_time (decision, event_time),
    KEY ix_guard_events_shadow (mode, predicted_decision, event_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



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

CREATE TABLE IF NOT EXISTS guard_schema_migrations (
    version INT UNSIGNED NOT NULL,
    filename VARCHAR(255) NOT NULL,
    applied_at DATETIME(6) NOT NULL,
    PRIMARY KEY (version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO guard_schema_migrations (version, filename, applied_at) VALUES (4, 'fresh-install-0.4.2', NOW(6));
