-- schema 158
ALTER TABLE `users` ADD `scope_teamgroups` TINYINT UNSIGNED NOT NULL DEFAULT 3;
UPDATE config SET conf_value = 158 WHERE conf_name = 'schema';
