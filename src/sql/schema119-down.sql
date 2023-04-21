-- revert schema 119
ALTER TABLE `users` DROP COLUMN `sc_search`;
ALTER TABLE `users` CHANGE `sc_favorite` `sc_submit` VARCHAR(1) NOT NULL DEFAULT 's';
UPDATE config SET conf_value = 118 WHERE conf_name = 'schema';
