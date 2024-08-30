-- schema 145 - drop old passwords with sha
ALTER TABLE `users` DROP COLUMN `salt`;
ALTER TABLE `users` DROP COLUMN `password`;
UPDATE config SET conf_value = 145 WHERE conf_name = 'schema';
