-- revert schema 137
INSERT INTO config (conf_name, conf_value) values ('deletable_xp', '1');
ALTER TABLE `teams` ADD `deletable_xp` TINYINT UNSIGNED NOT NULL DEFAULT 1;
ALTER TABLE `teams` ADD `deletable_item` TINYINT UNSIGNED NOT NULL DEFAULT 1;
UPDATE config SET conf_value = 136 WHERE conf_name = 'schema';
