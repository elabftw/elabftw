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
ALTER TABLE `experiments_categories`
  ADD KEY `fk_experiments_categories_teams_team_id` (`team`),
  ADD CONSTRAINT `fk_experiments_categories_teams_id` FOREIGN KEY (`team`) REFERENCES `teams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
-- now insert items_types into items_categories
INSERT INTO `items_categories` (
  `team`,
  `title`,
  `color`,
  `state`
)
SELECT
  `team`,
  `title`,
  `color`,
  `state`
FROM
  `items_types`;
ALTER TABLE `items_types`
  ADD COLUMN `category` INT UNSIGNED NULL DEFAULT NULL,
  ADD KEY `fk_items_types_category_items_categories_id` (`category`),
  ADD KEY `fk_items_types_category_items_categories_id` FOREIGN KEY (`category`) REFERENCES `items_categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- on table items, set a correct fk/index cascade because they can exist without a category
CALL DropFK('items', 'fk_items_items_types_id');
CALL DropIdx('items', 'fk_items_items_types_id');
ALTER TABLE `items`
  ADD KEY `fk_items_category_items_categories_id` (`category`),
  ADD KEY `fk_items_category_items_categories_id` FOREIGN KEY (`category`) REFERENCES `items_categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- add missing created_at and modified_at on experiments_categories
ALTER TABLE `experiments_categories`
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
