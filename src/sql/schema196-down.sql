ALTER TABLE `experiments` DROP COLUMN `hide_main_text`;
ALTER TABLE `items` DROP COLUMN `hide_main_text`;
ALTER TABLE `experiments_templates` DROP COLUMN `hide_main_text`;
ALTER TABLE `items_types` DROP COLUMN `hide_main_text`;
UPDATE config SET conf_value = 195 WHERE conf_name = 'schema';
