-- revert schema 158
ALTER TABLE `users` DROP COLUMN `teamgroups_scope`;
UPDATE config SET conf_value = 157 WHERE conf_name = 'schema';
