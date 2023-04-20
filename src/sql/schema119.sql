-- schema 119
ALTER TABLE `users` ADD `sc_search` VARCHAR(1) NOT NULL DEFAULT '/';
UPDATE config SET conf_value = 119 WHERE conf_name = 'schema';
