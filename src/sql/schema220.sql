-- schema 220
ALTER TABLE `authfail`
    ADD INDEX `idx_authfail_users_attempt_time` (`users_id`, `attempt_time`),
    ADD INDEX `idx_authfail_device_attempt_time` (`device_token`, `attempt_time`);
