-- schema 198
ALTER TABLE `users` ADD COLUMN `theme_variant` TINYINT UNSIGNED NOT NULL DEFAULT 0;
UPDATE config SET conf_value = 198 WHERE conf_name = 'schema';
