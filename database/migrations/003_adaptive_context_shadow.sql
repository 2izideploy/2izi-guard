ALTER TABLE guard_challenges
    ADD COLUMN predicted_decision VARCHAR(24) CHARACTER SET ascii COLLATE ascii_bin NULL AFTER attempt_count,
    ADD COLUMN interactive_required TINYINT(1) NOT NULL DEFAULT 0 AFTER predicted_decision,
    ADD COLUMN interactive_min_ms INT UNSIGNED NOT NULL DEFAULT 0 AFTER interactive_required;

ALTER TABLE guard_events
    ADD COLUMN predicted_decision VARCHAR(24) CHARACTER SET ascii COLLATE ascii_bin NULL AFTER latency_ms,
    ADD COLUMN mode VARCHAR(24) CHARACTER SET ascii COLLATE ascii_bin NULL AFTER predicted_decision,
    ADD KEY ix_guard_events_shadow (mode, predicted_decision, event_time);
