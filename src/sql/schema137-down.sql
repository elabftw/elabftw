-- revert schema 137
INSERT INTO config (conf_name, conf_value) values ('deletable_xp', '1');
ALTER TABLE `teams` ADD `deletable_xp` TINYINT UNSIGNED NOT NULL DEFAULT 1;
ALTER TABLE `teams` ADD `deletable_item` TINYINT UNSIGNED NOT NULL DEFAULT 1;
ALTER TABLE experiments_templates DROP COLUMN canread_target;
ALTER TABLE experiments_templates DROP COLUMN canwrite_target;
ALTER TABLE items_types DROP COLUMN canread_target;
ALTER TABLE items_types DROP COLUMN canwrite_target;
UPDATE config SET conf_value = 136 WHERE conf_name = 'schema';
