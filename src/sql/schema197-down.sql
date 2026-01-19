-- revert schema 197
ALTER TABLE `users` DROP COLUMN `dark_mode`;
UPDATE config SET conf_value = 196 WHERE conf_name = 'schema';
