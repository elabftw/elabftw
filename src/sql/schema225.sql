-- schema 225
-- The pairing of scope with teams_id/users_id is not expressed as a CHECK constraint:
-- MySQL refuses a CHECK on a column that takes part in a foreign key referential action,
-- and the cascading deletes matter more. Without them a deleted team or user would leave
-- webhook rows behind that keep receiving events. The invariant is guaranteed in the model
-- layer instead, where each scope is its own class and hardcodes both values.
CREATE TABLE `webhooks` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `scope` varchar(8) NOT NULL,
  `teams_id` int(10) UNSIGNED DEFAULT NULL,
  `users_id` int(10) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `url` varchar(512) NOT NULL,
  `secret` varchar(64) NOT NULL,
  `events` json NOT NULL,
  `enabled` tinyint NOT NULL DEFAULT 1,
  `consecutive_failures` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `last_error` text DEFAULT NULL,
  `disabled_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_webhooks_scope` (`scope`, `enabled`),
  KEY `idx_webhooks_teams_id` (`teams_id`),
  KEY `idx_webhooks_users_id` (`users_id`),
  CONSTRAINT `fk_webhooks_teams_id` FOREIGN KEY (`teams_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_webhooks_users_id` FOREIGN KEY (`users_id`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

CREATE TABLE `webhooks_queue` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `webhooks_id` int(10) UNSIGNED NOT NULL,
  `event_id` char(32) NOT NULL,
  `event` varchar(64) NOT NULL,
  `body` json NOT NULL,
  `state` tinyint NOT NULL DEFAULT 0,
  `attempts` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `claim_token` char(32) DEFAULT NULL,
  `next_attempt_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `delivered_at` datetime DEFAULT NULL,
  `last_error` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_webhooks_queue_drain` (`state`, `next_attempt_at`),
  KEY `idx_webhooks_queue_claim_token` (`claim_token`),
  KEY `idx_webhooks_queue_webhooks_id` (`webhooks_id`),
  CONSTRAINT `fk_webhooks_queue_webhooks_id` FOREIGN KEY (`webhooks_id`) REFERENCES `webhooks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;
