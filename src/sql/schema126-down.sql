-- revert schema 126
ALTER TABLE `experiments_revisions` CHANGE `created_at` `savedate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `items_revisions` CHANGE `created_at` `savedate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `experiments_templates_revisions` CHANGE `created_at` `savedate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
UPDATE config SET conf_value = 125 WHERE conf_name = 'schema';
