-- schema 128 adding status to items and categories to experiments
RENAME TABLE `status` TO `experiments_status`;
CREATE TABLE `items_status` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `team` int UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `color` varchar(6) NOT NULL,
  `is_default` tinyint UNSIGNED DEFAULT NULL,
  `ordering` int UNSIGNED DEFAULT NULL,
  `state` INT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;
ALTER TABLE `items` ADD `status` INT UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `experiments` CHANGE `category` `status` INT UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `experiments` ADD `category` INT UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `experiments_status` ADD `state` INT UNSIGNED NOT NULL DEFAULT 1;
CREATE TABLE `experiments_categories` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `team` int UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `color` varchar(6) NOT NULL,
  `is_default` tinyint UNSIGNED DEFAULT NULL,
  `ordering` int UNSIGNED DEFAULT NULL,
  `state` INT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;
ALTER TABLE `items` CHANGE `category` `category` INT UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `experiments_templates` ADD `status` INT UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `experiments_templates` ADD `category` INT UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `items_types` ADD `status` INT UNSIGNED NULL DEFAULT NULL;
UPDATE config SET conf_value = 128 WHERE conf_name = 'schema';
