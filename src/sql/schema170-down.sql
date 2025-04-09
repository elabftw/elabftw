-- revert schema 170
ALTER TABLE `users` DROP COLUMN `enforce_exclusive_edit_mode`;
UPDATE config SET conf_value = 169 WHERE conf_name = 'schema';
