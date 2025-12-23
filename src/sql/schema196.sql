-- schema 196
ALTER TABLE `experiments` ADD COLUMN `hide_main_text` TINYINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `items` ADD COLUMN `hide_main_text` TINYINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `experiments_templates` ADD COLUMN `hide_main_text` TINYINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `items_types` ADD COLUMN `hide_main_text` TINYINT UNSIGNED NOT NULL DEFAULT 0;

-- migrate legacy entries:
-- display_main_text = false -> hide_main_text = 1
-- display_main_text = true -> hide_main_text = 0
-- missing key (no metadata or no elabftw object) -> hide_main_text = 0
-- experiments
UPDATE experiments
SET hide_main_text = 1
WHERE JSON_EXTRACT(metadata, '$.elabftw.display_main_text') = false;
-- resources
UPDATE items
SET hide_main_text = 1
WHERE JSON_EXTRACT(metadata, '$.elabftw.display_main_text') = false;
-- experiment templates
UPDATE experiments_templates
SET hide_main_text = 1
WHERE JSON_EXTRACT(metadata, '$.elabftw.display_main_text') = false;
-- resource templates
UPDATE items_types
SET hide_main_text = 1
WHERE JSON_EXTRACT(metadata, '$.elabftw.display_main_text') = false;

-- cleanup: remove legacy JSON key
-- experiments
UPDATE experiments
SET metadata = JSON_REMOVE(metadata, '$.elabftw.display_main_text')
WHERE JSON_EXTRACT(metadata, '$.elabftw.display_main_text') IS NOT NULL;
-- resources
UPDATE items
SET metadata = JSON_REMOVE(metadata, '$.elabftw.display_main_text')
WHERE JSON_EXTRACT(metadata, '$.elabftw.display_main_text') IS NOT NULL;
-- experiment templates
UPDATE experiments_templates
SET metadata = JSON_REMOVE(metadata, '$.elabftw.display_main_text')
WHERE JSON_EXTRACT(metadata, '$.elabftw.display_main_text') IS NOT NULL;
-- resource templates
UPDATE items_types
SET metadata = JSON_REMOVE(metadata, '$.elabftw.display_main_text')
WHERE JSON_EXTRACT(metadata, '$.elabftw.display_main_text') IS NOT NULL;
