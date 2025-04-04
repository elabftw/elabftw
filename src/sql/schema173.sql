-- schema 173
-- clear the tables
DELETE FROM `experiments_edit_mode`;
DELETE FROM `experiments_templates_edit_mode`;
DELETE FROM `items_edit_mode`;
DELETE FROM `items_types_edit_mode`;
-- rename specific column names to entity_id
ALTER TABLE `experiments_edit_mode` CHANGE `experiments_id` `entity_id` INT UNSIGNED NOT NULL;
-- add missing default value for locked_at
ALTER TABLE `experiments_edit_mode` CHANGE `locked_at` `locked_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE `experiments_templates_edit_mode` CHANGE `experiments_templates_id` `entity_id` INT UNSIGNED NOT NULL;
ALTER TABLE `experiments_templates_edit_mode` CHANGE `locked_at` `locked_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE `items_edit_mode` CHANGE `items_id` `entity_id` INT UNSIGNED NOT NULL;
ALTER TABLE `items_edit_mode` CHANGE `locked_at` `locked_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE `items_types_edit_mode` CHANGE `items_types_id` `entity_id` INT UNSIGNED NOT NULL;
ALTER TABLE `items_types_edit_mode` CHANGE `locked_at` `locked_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `experiments_edit_mode` DROP INDEX `idx_experiments_edit_mode_all_columns`;
ALTER TABLE `experiments_templates_edit_mode` DROP INDEX `idx_experiments_templates_edit_mode_all_columns`;
ALTER TABLE `items_edit_mode` DROP INDEX `idx_items_edit_mode_all_columns`;
ALTER TABLE `items_types_edit_mode` DROP INDEX `idx_items_types_edit_mode_all_columns`;
