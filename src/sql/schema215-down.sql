-- revert schema 215
ALTER TABLE `experiments_status` ADD COLUMN `is_default` TINYINT UNSIGNED DEFAULT NULL;
ALTER TABLE `experiments_categories` ADD COLUMN `is_default` TINYINT UNSIGNED DEFAULT NULL;
ALTER TABLE `items_status` ADD COLUMN `is_default` TINYINT UNSIGNED DEFAULT NULL;
ALTER TABLE `items_categories` ADD COLUMN `is_default` TINYINT UNSIGNED DEFAULT NULL;

UPDATE config SET conf_value = 214 WHERE conf_name = 'schema';
