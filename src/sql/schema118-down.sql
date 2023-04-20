-- revert schema 118
ALTER TABLE `users` DROP COLUMN `sc_search`;
UPDATE config SET conf_value = 117 WHERE conf_name = 'schema';
