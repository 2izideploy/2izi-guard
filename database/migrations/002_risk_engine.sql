ALTER TABLE guard_sessions
    ADD COLUMN successful_challenges INT UNSIGNED NOT NULL DEFAULT 0 AFTER failed_actions,
    ADD COLUMN failed_challenges INT UNSIGNED NOT NULL DEFAULT 0 AFTER successful_challenges,
    ADD COLUMN pow_ema_ms DECIMAL(10,2) NULL AFTER failed_challenges,
    ADD COLUMN last_pow_difficulty TINYINT UNSIGNED NULL AFTER pow_ema_ms;

ALTER TABLE guard_challenges
    ADD COLUMN reason_codes JSON NULL AFTER risk_score,
    ADD COLUMN honeypot_name VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL AFTER reason_codes,
    ADD COLUMN honeypot_required TINYINT(1) NOT NULL DEFAULT 0 AFTER honeypot_name,
    ADD COLUMN client_locale VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NULL AFTER honeypot_required;
