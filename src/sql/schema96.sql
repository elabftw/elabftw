-- Schema 96
-- default for users.pdfa is 0 now
ALTER TABLE `users` CHANGE `pdfa` `pdfa` TINYINT UNSIGNED NOT NULL DEFAULT '0';
UPDATE config SET conf_value = 96 WHERE conf_name = 'schema';
