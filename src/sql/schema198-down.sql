-- revert schema 198
ALTER TABLE `users` DROP COLUMN `theme_variant`;
UPDATE config SET conf_value = 197 WHERE conf_name = 'schema';
