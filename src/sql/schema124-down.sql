-- revert schema 124
ALTER TABLE `users` DROP COLUMN `disable_shortcuts`;
UPDATE config SET conf_value = 123 WHERE conf_name = 'schema';
