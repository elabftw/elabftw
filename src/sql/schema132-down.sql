-- revert schema 132
ALTER TABLE `users` CHANGE `orderby` `orderby` VARCHAR(255) NOT NULL DEFAULT 'date';
UPDATE config SET conf_value = 131 WHERE conf_name = 'schema';
