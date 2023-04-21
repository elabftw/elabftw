-- schema 119
ALTER TABLE `users` ADD `sc_search` VARCHAR(1) NOT NULL DEFAULT 's';
ALTER TABLE `users` CHANGE `sc_submit` `sc_favorite` VARCHAR(1) NOT NULL DEFAULT 'f';
-- this is destructive but nobody is using it anyway so it's okay
UPDATE `users` SET `sc_favorite` = 'f';
UPDATE config SET conf_value = 119 WHERE conf_name = 'schema';
