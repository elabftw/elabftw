-- Schema 89
-- add auth_service column for users
ALTER TABLE `users` ADD `auth_service` TINYINT(1) UNSIGNED NULL DEFAULT NULL;
UPDATE config SET conf_value = 89 WHERE conf_name = 'schema';
