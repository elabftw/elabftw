-- schema 219
CREATE TABLE `mfa_rate_limits` (
  `users_id` int(10) UNSIGNED NOT NULL,
  `failed_attempts` tinyint UNSIGNED NOT NULL DEFAULT 0,
  `first_failed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `locked_until` datetime DEFAULT NULL,
  PRIMARY KEY (`users_id`),
  CONSTRAINT `fk_mfa_rate_limits_user`
    FOREIGN KEY (`users_id`) REFERENCES `users` (`userid`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;
ALTER TABLE `authfail`
    ADD INDEX `idx_authfail_users_attempt_time` (`users_id`, `attempt_time`),
    ADD INDEX `idx_authfail_device_attempt_time` (`device_token`, `attempt_time`);
