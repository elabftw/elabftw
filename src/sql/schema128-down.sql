-- revert schema 128
RENAME TABLE `experiments_status` TO `status`;
DROP TABLE IF EXISTS `items_status`;
ALTER TABLE `items` DROP COLUMN `status`;
ALTER TABLE `experiments` DROP COLUMN `category`;
ALTER TABLE `experiments` CHANGE `status` `category` INT UNSIGNED NOT NULL;
DROP TABLE IF EXISTS `experiments_categories`;
UPDATE config SET conf_value = 127 WHERE conf_name = 'schema';
