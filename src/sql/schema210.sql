-- schema 210
ALTER TABLE `teams` ADD `custom_units` VARCHAR(255) NULL DEFAULT NULL;
ALTER TABLE `teams` ADD `hidden_units` VARCHAR(255) NULL DEFAULT NULL;
UPDATE config SET conf_value = 210 WHERE conf_name = 'schema';
