-- schema 124
ALTER TABLE `users` ADD `disable_shortcuts` TINYINT UNSIGNED NOT NULL DEFAULT 0;
UPDATE config SET conf_value = 124 WHERE conf_name = 'schema';
