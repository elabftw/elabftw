-- Schema 96
-- remove chem_editor from users
ALTER TABLE `users` DROP COLUMN `chem_editor`;
ALTER TABLE `users` DROP COLUMN `phone`;
ALTER TABLE `users` DROP COLUMN `cellphone`;
ALTER TABLE `users` DROP COLUMN `website`;
ALTER TABLE `experiments` DROP COLUMN `timestamptoken`;
ALTER TABLE `users` ADD `pdf_sig` TINYINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `items_types` CHANGE `name` `title` VARCHAR(255) NOT NULL;
ALTER TABLE `status` CHANGE `name` `title` VARCHAR(255) NOT NULL;
UPDATE config SET conf_value = 98 WHERE conf_name = 'schema';
