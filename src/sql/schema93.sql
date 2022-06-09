-- Schema 93
-- add content_type column to entities so we know what is inside (html or md)
ALTER TABLE `experiments` ADD `content_type` TINYINT(1) NOT NULL DEFAULT 1;
ALTER TABLE `experiments_revisions` ADD `content_type` TINYINT(1) NOT NULL DEFAULT 1;
ALTER TABLE `experiments_templates` ADD `content_type` TINYINT(1) NOT NULL DEFAULT 1;
ALTER TABLE `experiments_templates_revisions` ADD `content_type` TINYINT(1) NOT NULL DEFAULT 1;
ALTER TABLE `items` ADD `content_type` TINYINT(1) NOT NULL DEFAULT 1;
ALTER TABLE `items_revisions` ADD `content_type` TINYINT(1) NOT NULL DEFAULT 1;
ALTER TABLE `items_types` ADD `content_type` TINYINT(1) NOT NULL DEFAULT 1;
UPDATE config SET conf_value = 93 WHERE conf_name = 'schema';
