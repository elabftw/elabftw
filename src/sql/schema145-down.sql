-- revert schema 145
ALTER TABLE `users` ADD `salt` varchar(255) NULL DEFAULT NULL;
ALTER TABLE `users` ADD `password` varchar(255) NULL DEFAULT NULL;
UPDATE config SET conf_value = 144 WHERE conf_name = 'schema';
