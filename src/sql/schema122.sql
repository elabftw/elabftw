-- schema 122
ALTER TABLE `users` ADD `token_created_at` TIMESTAMP NULL DEFAULT NULL;
-- invalidate all existing tokens too
UPDATE `users` SET token = NULL;
INSERT INTO config (conf_name, conf_value) VALUES ('cookie_validity_time', '43200');
UPDATE config SET conf_value = 122 WHERE conf_name = 'schema';
