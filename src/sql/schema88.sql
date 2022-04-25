-- Schema 88
-- remove json_editor user config
ALTER TABLE `users` DROP COLUMN `json_editor`;
UPDATE config SET conf_value = 88 WHERE conf_name = 'schema';
