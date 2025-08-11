-- schema 183
CREATE TABLE `items_categories` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `team` int UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `color` varchar(6) NOT NULL,
  `is_default` tinyint UNSIGNED DEFAULT NULL,
  `ordering` int UNSIGNED DEFAULT NULL,
  `state` INT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  CONSTRAINT FOREIGN KEY (`team`) REFERENCES `teams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;
-- add missing fk on experiments_categories
CALL DropFK('experiments_categories', 'fk_experiments_categories_teams_id');
CALL DropIdx('experiments_categories', 'fk_experiments_categories_teams_team_id');
CALL DropFK('experiments_categories', 'fk_experiments_categories_teams_team_id');
ALTER TABLE `experiments_categories`
  ADD CONSTRAINT FOREIGN KEY (`team`) REFERENCES `teams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
-- now insert items_types into items_categories
INSERT INTO `items_categories` (
  `id`,
  `team`,
  `title`,
  `color`,
  `state`
)
SELECT
  `id`,
  `team`,
  `title`,
  `color`,
  `state`
FROM
  `items_types`;
ALTER TABLE `items_types`
  ADD COLUMN `category` INT UNSIGNED NULL DEFAULT NULL,
  ADD CONSTRAINT FOREIGN KEY (`category`) REFERENCES `items_categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- on table items, set a correct fk/index cascade because they can exist without a category
CALL DropFK('items', 'fk_items_items_types_id');
CALL DropIdx('items', 'fk_items_items_types_id');
ALTER TABLE `items`
  ADD CONSTRAINT FOREIGN KEY (`category`) REFERENCES `items_categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- add missing created_at and modified_at on experiments_categories, experiments_status and items_status
ALTER TABLE `experiments_categories`
  ADD COLUMN `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ADD COLUMN `modified_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `experiments_status`
  ADD COLUMN `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ADD COLUMN `modified_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `items_status`
  ADD COLUMN `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ADD COLUMN `modified_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- give status to experiments templates too
CREATE TABLE `experiments_templates_status` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `team` int UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `color` varchar(6) NOT NULL,
  `is_default` tinyint UNSIGNED DEFAULT NULL,
  `ordering` int UNSIGNED DEFAULT NULL,
  `state` INT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  CONSTRAINT FOREIGN KEY (`team`) REFERENCES `teams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

-- drop the comment template stuff from teams
CALL DropColumn('teams', 'common_template');
CALL DropColumn('teams', 'common_template_md');

CREATE TABLE `items_types_comments` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `item_id` int(10) UNSIGNED NOT NULL,
  `comment` text NOT NULL,
  `userid` int(10) UNSIGNED NOT NULL,
  `immutable` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  CONSTRAINT FOREIGN KEY (`item_id`) REFERENCES `items_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `pin_items_types2users` (
  `users_id` int UNSIGNED NOT NULL,
  `entity_id` int UNSIGNED NOT NULL,
  PRIMARY KEY (`users_id`,`entity_id`),
  CONSTRAINT FOREIGN KEY (`entity_id`) REFERENCES `items_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT FOREIGN KEY (`users_id`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `items_types_status` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `team` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `color` varchar(6) NOT NULL,
  `is_default` tinyint UNSIGNED DEFAULT NULL,
  `ordering` int(10) UNSIGNED DEFAULT NULL,
  `state` INT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  CONSTRAINT FOREIGN KEY (`team`) REFERENCES `teams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

ALTER TABLE `users` ADD `scope_items_types` TINYINT UNSIGNED NOT NULL DEFAULT 2;

ALTER TABLE `experiments_changelog`
  ADD COLUMN `modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE `experiments_revisions`
  ADD COLUMN `modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE `experiments_templates_changelog`
  ADD COLUMN `modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE `experiments_templates_revisions`
  ADD COLUMN `modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE `items_changelog`
  ADD COLUMN `modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE `items_revisions`
  ADD COLUMN `modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

CREATE TABLE `items_types_revisions` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `item_id` int(10) UNSIGNED NOT NULL,
  `body` mediumtext NOT NULL,
  `content_type` tinyint NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `userid` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;
ALTER TABLE `items_types_revisions`
  ADD CONSTRAINT FOREIGN KEY (`item_id`) REFERENCES `items_types`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT FOREIGN KEY (`userid`) REFERENCES `users`(`userid`) ON DELETE CASCADE ON UPDATE CASCADE;
