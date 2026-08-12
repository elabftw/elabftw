-- revert schema 220
ALTER TABLE `storage_units` DROP COLUMN `capacity`;
UPDATE config SET conf_value = 219 WHERE conf_name = 'schema';
