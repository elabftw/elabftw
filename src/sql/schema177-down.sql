-- revert schema 177
ALTER TABLE `users` DROP COLUMN `scope_events`;
UPDATE config SET conf_value = 176 WHERE conf_name = 'schema';
