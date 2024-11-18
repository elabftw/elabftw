-- schema 167
ALTER TABLE `users` ADD `show_weekends` TINYINT UNSIGNED NOT NULL DEFAULT 0;
UPDATE config SET conf_value = 167 WHERE conf_name = 'schema';
