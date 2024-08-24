-- revert schema 165
ALTER TABLE `items_types` DROP COLUMN `userid`;
UPDATE config SET conf_value = 164 WHERE conf_name = 'schema';
