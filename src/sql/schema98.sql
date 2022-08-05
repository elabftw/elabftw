-- Schema 96
-- remove chem_editor from users
ALTER TABLE `users` DROP COLUMN `chem_editor`;
ALTER TABLE `users` DROP COLUMN `phone`;
ALTER TABLE `users` DROP COLUMN `cellphone`;
ALTER TABLE `users` DROP COLUMN `website`;
ALTER TABLE `experiments` DROP COLUMN `timestamptoken`;
UPDATE config SET conf_value = 98 WHERE conf_name = 'schema';
