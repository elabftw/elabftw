-- revert schema 171
ALTER TABLE `experiments_templates` DROP COLUMN `canread_is_immutable`;
ALTER TABLE `experiments_templates` DROP COLUMN `canwrite_is_immutable`;
ALTER TABLE `experiments` DROP COLUMN `canread_is_immutable`;
ALTER TABLE `experiments` DROP COLUMN `canwrite_is_immutable`;
ALTER TABLE `items` DROP COLUMN `canread_is_immutable`;
ALTER TABLE `items` DROP COLUMN `canwrite_is_immutable`;
ALTER TABLE `items_types` DROP COLUMN `canread_is_immutable`;
ALTER TABLE `items_types` DROP COLUMN `canwrite_is_immutable`;
ALTER TABLE `teams`
  ADD COLUMN `force_canread` JSON NOT NULL,
  ADD COLUMN `force_canwrite` JSON NOT NULL,
  ADD COLUMN `do_force_canread` tinyint UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN `do_force_canwrite` tinyint UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN `link_name` VARCHAR(255) DEFAULT 'Documentation',
  ADD COLUMN `link_href` VARCHAR(255) DEFAULT 'https://doc.elabftw.net';
UPDATE config SET conf_value = 170 WHERE conf_name = 'schema';
