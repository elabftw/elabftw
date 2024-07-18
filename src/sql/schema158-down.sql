-- revert schema 158
ALTER TABLE `users` DROP COLUMN `scope_teamgroups`;
UPDATE config SET conf_value = 157 WHERE conf_name = 'schema';
