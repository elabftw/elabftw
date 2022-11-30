-- Schema 103
ALTER TABLE `users` DROP COLUMN `pdfa`;
UPDATE config SET conf_value = 103 WHERE conf_name = 'schema';
