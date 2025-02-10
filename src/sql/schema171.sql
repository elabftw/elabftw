-- schema 171
ALTER TABLE `experiments` ADD `canread_is_immutable` TINYINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `experiments` ADD `canwrite_is_immutable` TINYINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `experiments_templates` ADD `canread_is_immutable` TINYINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `experiments_templates` ADD `canwrite_is_immutable` TINYINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `experiments_templates` ADD `date` date NULL DEFAULT NULL;
ALTER TABLE `experiments_templates` ADD `elabid` VARCHAR(255) NULL DEFAULT NULL;
ALTER TABLE `items_types` ADD `date` date NULL DEFAULT NULL;
ALTER TABLE `items_types` ADD `elabid` VARCHAR(255) NULL DEFAULT NULL;
ALTER TABLE `items` ADD `canread_is_immutable` TINYINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `items` ADD `canwrite_is_immutable` TINYINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `items_types` ADD `canread_is_immutable` TINYINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `items_types` ADD `canwrite_is_immutable` TINYINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `teams`
  DROP `force_canread`,
  DROP `force_canwrite`,
  DROP `do_force_canread`,
  DROP `do_force_canwrite`,
  DROP `link_name`,
  DROP `link_href`;
