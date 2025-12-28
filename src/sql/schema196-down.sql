-- add the key where it was already present
UPDATE experiments SET metadata = JSON_SET(metadata, '$.elabftw.display_main_text', false) WHERE hide_main_text = 1;
UPDATE items SET metadata = JSON_SET(metadata, '$.elabftw.display_main_text', false) WHERE hide_main_text = 1;
UPDATE experiments_templates SET metadata = JSON_SET(metadata, '$.elabftw.display_main_text', false) WHERE hide_main_text = 1;
UPDATE items_types SET metadata = JSON_SET(metadata, '$.elabftw.display_main_text', false) WHERE hide_main_text = 1;
ALTER TABLE `experiments` DROP COLUMN `hide_main_text`;
ALTER TABLE `items` DROP COLUMN `hide_main_text`;
ALTER TABLE `experiments_templates` DROP COLUMN `hide_main_text`;
ALTER TABLE `items_types` DROP COLUMN `hide_main_text`;

UPDATE config SET conf_value = 195 WHERE conf_name = 'schema';
