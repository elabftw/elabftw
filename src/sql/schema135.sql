-- schema 135
ALTER TABLE `users` ADD `scope_experiments` TINYINT UNSIGNED NOT NULL DEFAULT 2;
ALTER TABLE `users` ADD `scope_items` TINYINT UNSIGNED NOT NULL DEFAULT 2;
ALTER TABLE `users` ADD `scope_experiments_templates` TINYINT UNSIGNED NOT NULL DEFAULT 2;
ALTER TABLE `users` ADD `scope_bookable` TINYINT UNSIGNED NOT NULL DEFAULT 2;
ALTER TABLE `users` DROP COLUMN `show_public`;
ALTER TABLE `users` DROP COLUMN `show_team`;
ALTER TABLE `users` DROP COLUMN `show_team_templates`;
UPDATE config SET conf_value = 135 WHERE conf_name = 'schema';
