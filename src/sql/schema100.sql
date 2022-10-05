-- Schema 100
ALTER TABLE `users` ADD `valid_until` DATE NULL DEFAULT NULL;
UPDATE config SET conf_value = 100 WHERE conf_name = 'schema';
