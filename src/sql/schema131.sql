-- schema 131
ALTER TABLE `experiments` ADD `custom_id` INT UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `items` ADD `custom_id` INT UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `experiments_templates` ADD `custom_id` INT UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `items_types` ADD `custom_id` INT UNSIGNED NULL DEFAULT NULL;
UPDATE config SET conf_value = 131 WHERE conf_name = 'schema';
