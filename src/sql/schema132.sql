-- schema 132
ALTER TABLE `users` CHANGE `orderby` `orderby` VARCHAR(255) NOT NULL DEFAULT 'lastchange';
UPDATE config SET conf_value = 132 WHERE conf_name = 'schema';
