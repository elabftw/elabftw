-- revert schema 169
ALTER TABLE `users` DROP COLUMN `enforce_exclusive_edit_mode`;
UPDATE config SET conf_value = 168 WHERE conf_name = 'schema';
