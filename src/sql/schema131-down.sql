-- revert schema 131
ALTER TABLE `experiments` DROP COLUMN `custom_id`;
ALTER TABLE `items` DROP COLUMN `custom_id`;
ALTER TABLE `experiments_templates` DROP COLUMN `custom_id`;
ALTER TABLE `items_types` DROP COLUMN `custom_id`;
UPDATE config SET conf_value = 130 WHERE conf_name = 'schema';
