-- schema 126
ALTER TABLE `experiments_revisions` CHANGE `savedate` `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `items_revisions` CHANGE `savedate` `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `experiments_templates_revisions` CHANGE `savedate` `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
UPDATE config SET conf_value = 126 WHERE conf_name = 'schema';
