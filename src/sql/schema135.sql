-- schema 135
ALTER TABLE `users` ADD `scope` TINYINT UNSIGNED NOT NULL DEFAULT 2;
ALTER TABLE `users` DROP COLUMN `show_public`;
ALTER TABLE `users` DROP COLUMN `show_team`;
ALTER TABLE `users` DROP COLUMN `show_team_templates`;
UPDATE config SET conf_value = 135 WHERE conf_name = 'schema';
