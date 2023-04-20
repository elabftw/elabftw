-- revert schema 119
ALTER TABLE `users` DROP COLUMN `sc_search`;
UPDATE config SET conf_value = 118 WHERE conf_name = 'schema';
