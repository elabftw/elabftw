-- Schema 96
-- remove chem_editor from users
ALTER TABLE `users` DROP COLUMN `chem_editor`;
UPDATE config SET conf_value = 98 WHERE conf_name = 'schema';
