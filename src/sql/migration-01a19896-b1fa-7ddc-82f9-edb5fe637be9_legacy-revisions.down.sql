-- revert migration 01a19896-b1fa-7ddc-82f9-edb5fe637be9

-- The data step of this migration, Elabftw\Services\LegacyRevisionsMigration, runs after this file:
-- it copies every body revision into these tables.

CREATE TABLE IF NOT EXISTS `experiments_revisions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `item_id` int unsigned NOT NULL,
  `body` mediumtext NOT NULL,
  `content_type` tinyint NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `userid` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_experiments_revisions_experiments_id` (`item_id`),
  KEY `fk_experiments_revisions_users_userid` (`userid`),
  CONSTRAINT `fk_experiments_revisions_experiments_id` FOREIGN KEY (`item_id`) REFERENCES `experiments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_experiments_revisions_users_userid` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `items_revisions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `item_id` int unsigned NOT NULL,
  `body` mediumtext NOT NULL,
  `content_type` tinyint NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `userid` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_items_revisions_items_id` (`item_id`),
  KEY `fk_items_revisions_users_userid` (`userid`),
  CONSTRAINT `fk_items_revisions_items_id` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_items_revisions_users_userid` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `experiments_templates_revisions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `item_id` int unsigned NOT NULL,
  `body` mediumtext NOT NULL,
  `content_type` tinyint NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `userid` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_experiments_templates_revisions_experiments_templates_id` (`item_id`),
  KEY `fk_experiments_templates_revisions_users_userid` (`userid`),
  CONSTRAINT `fk_experiments_templates_revisions_experiments_templates_id` FOREIGN KEY (`item_id`) REFERENCES `experiments_templates` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_experiments_templates_revisions_users_userid` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `items_types_revisions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `item_id` int unsigned NOT NULL,
  `body` mediumtext NOT NULL,
  `content_type` tinyint NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `userid` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `item_id` (`item_id`),
  KEY `userid` (`userid`),
  CONSTRAINT `items_types_revisions_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `items_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `items_types_revisions_ibfk_2` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 0 keeps every revision: the previous code would otherwise delete the restored history on the next save
INSERT IGNORE INTO `config` (`conf_name`, `conf_value`) VALUES ('max_revisions', '0'), ('min_delta_revisions', '100'), ('min_days_revisions', '23');
