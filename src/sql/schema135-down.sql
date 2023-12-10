-- revert schema 135
ALTER TABLE `users` DROP COLUMN `scope_experiments`;
ALTER TABLE `users` DROP COLUMN `scope_items`;
ALTER TABLE `users` DROP COLUMN `scope_experiments_templates`;
ALTER TABLE `users` ADD `show_public` TINYINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `users` ADD `show_team` TINYINT UNSIGNED NOT NULL DEFAULT 1;
ALTER TABLE `users` ADD `show_team_templates` TINYINT UNSIGNED NOT NULL DEFAULT 0;
UPDATE config SET conf_value = 134 WHERE conf_name = 'schema';
