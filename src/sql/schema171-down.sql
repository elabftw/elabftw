-- revert schema 171
ALTER TABLE `experiments_templates` DROP COLUMN `canread_is_immutable`;
ALTER TABLE `experiments_templates` DROP COLUMN `canwrite_is_immutable`;
UPDATE config SET conf_value = 170 WHERE conf_name = 'schema';
