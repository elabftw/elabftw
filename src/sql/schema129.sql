-- schema 129
ALTER TABLE `api_keys` ADD `last_used_at` TIMESTAMP NULL DEFAULT NULL;
UPDATE config SET conf_value = 129 WHERE conf_name = 'schema';
