-- Schema 90
-- drop is_timestampable from status
ALTER TABLE `status` DROP COLUMN `is_timestampable`;
UPDATE config SET conf_value = 90 WHERE conf_name = 'schema';
