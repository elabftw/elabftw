-- revert schema 198
ALTER TABLE `users` DROP COLUMN `dark_mode`;
UPDATE config SET conf_value = 197 WHERE conf_name = 'schema';
