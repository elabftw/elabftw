-- revert schema 128
ALTER TABLE `experiments_status` DROP COLUMN `state`;
RENAME TABLE `experiments_status` TO `status`;
DROP TABLE IF EXISTS `items_status`;
ALTER TABLE `items` DROP COLUMN `status`;
ALTER TABLE `experiments` DROP COLUMN `category`;
-- recreate it as nullable to avoid fk issues
ALTER TABLE `experiments` CHANGE `status` `category` INT UNSIGNED NULL DEFAULT NULL;
DROP TABLE IF EXISTS `experiments_categories`;
ALTER TABLE `experiments_templates` DROP COLUMN `status`;
ALTER TABLE `experiments_templates` DROP COLUMN `category`;
ALTER TABLE `items_types` DROP COLUMN `status`;
UPDATE config SET conf_value = 127 WHERE conf_name = 'schema';
