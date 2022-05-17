-- Schema 91
-- drop use_ove from user prefs
ALTER TABLE `users` DROP COLUMN `use_ove`;
UPDATE config SET conf_value = 91 WHERE conf_name = 'schema';
