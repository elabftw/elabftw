-- revert schema 164
INSERT INTO config (conf_name, conf_value) values ('trust_imported_archives', '0');
ALTER TABLE `items_types` DROP COLUMN `userid`;
ALTER TABLE `items_types` DROP COLUMN `rating`;
ALTER TABLE `experiments_templates` DROP COLUMN `rating`;
UPDATE config SET conf_value = 163 WHERE conf_name = 'schema';
