-- revert schema 133
ALTER TABLE `experiments` DROP INDEX `unique_experiments_custom_id`;
ALTER TABLE `experiments_templates` DROP INDEX `unique_experiments_templates_custom_id`;
ALTER TABLE `items` DROP INDEX `unique_items_custom_id`;
ALTER TABLE `items_types` DROP INDEX `unique_items_types_custom_id`;
UPDATE config SET conf_value = 132 WHERE conf_name = 'schema';
