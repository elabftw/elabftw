-- schema 118
ALTER TABLE `users` ADD `sc_search` VARCHAR(1) NOT NULL DEFAULT '/';
UPDATE config SET conf_value = 118 WHERE conf_name = 'schema';
