-- migration 01a19847-4937-7281-9fe0-b4714ccec8b9

-- Full history of the main text, see src/Models/BodyRevisions.php.
-- Rows are append-only: body_zstd is a zstd frame of the body, compressed with the body of parent_id as dictionary,
-- or without dictionary for keyframes (parent_id IS NULL, depth 0).
-- users_id is SET NULL on user deletion: deleting a row would break every revision chained to it.
-- The legacy *_revisions tables are kept untouched.

CREATE TABLE IF NOT EXISTS `experiments_body_revisions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `entity_id` INT UNSIGNED NOT NULL,
  `parent_id` INT UNSIGNED NULL DEFAULT NULL,
  `depth` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `body_zstd` LONGBLOB NOT NULL,
  `body_sha256` CHAR(64) NOT NULL,
  `content_type` TINYINT NOT NULL DEFAULT 1,
  `users_id` INT UNSIGNED NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_experiments_body_revisions_entity_id` (`entity_id`, `id`),
  CONSTRAINT `fk_experiments_body_revisions_experiments_id`
    FOREIGN KEY (`entity_id`) REFERENCES `experiments` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_experiments_body_revisions_users_userid`
    FOREIGN KEY (`users_id`) REFERENCES `users` (`userid`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `items_body_revisions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `entity_id` INT UNSIGNED NOT NULL,
  `parent_id` INT UNSIGNED NULL DEFAULT NULL,
  `depth` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `body_zstd` LONGBLOB NOT NULL,
  `body_sha256` CHAR(64) NOT NULL,
  `content_type` TINYINT NOT NULL DEFAULT 1,
  `users_id` INT UNSIGNED NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_items_body_revisions_entity_id` (`entity_id`, `id`),
  CONSTRAINT `fk_items_body_revisions_items_id`
    FOREIGN KEY (`entity_id`) REFERENCES `items` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_items_body_revisions_users_userid`
    FOREIGN KEY (`users_id`) REFERENCES `users` (`userid`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `experiments_templates_body_revisions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `entity_id` INT UNSIGNED NOT NULL,
  `parent_id` INT UNSIGNED NULL DEFAULT NULL,
  `depth` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `body_zstd` LONGBLOB NOT NULL,
  `body_sha256` CHAR(64) NOT NULL,
  `content_type` TINYINT NOT NULL DEFAULT 1,
  `users_id` INT UNSIGNED NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_experiments_templates_body_revisions_entity_id` (`entity_id`, `id`),
  CONSTRAINT `fk_experiments_templates_body_revisions_experiments_templates_id`
    FOREIGN KEY (`entity_id`) REFERENCES `experiments_templates` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_experiments_templates_body_revisions_users_userid`
    FOREIGN KEY (`users_id`) REFERENCES `users` (`userid`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `items_types_body_revisions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `entity_id` INT UNSIGNED NOT NULL,
  `parent_id` INT UNSIGNED NULL DEFAULT NULL,
  `depth` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `body_zstd` LONGBLOB NOT NULL,
  `body_sha256` CHAR(64) NOT NULL,
  `content_type` TINYINT NOT NULL DEFAULT 1,
  `users_id` INT UNSIGNED NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_items_types_body_revisions_entity_id` (`entity_id`, `id`),
  CONSTRAINT `fk_items_types_body_revisions_items_types_id`
    FOREIGN KEY (`entity_id`) REFERENCES `items_types` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_items_types_body_revisions_users_userid`
    FOREIGN KEY (`users_id`) REFERENCES `users` (`userid`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;
