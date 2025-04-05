-- revert schema 173
ALTER TABLE `experiments_edit_mode` CHANGE `entity_id` `experiments_id` INT UNSIGNED NOT NULL;
ALTER TABLE `experiments_templates_edit_mode` CHANGE `entity_id` `experiments_templates_id` INT UNSIGNED NOT NULL;
ALTER TABLE `items_edit_mode` CHANGE `entity_id` `items_id` INT UNSIGNED NOT NULL;
ALTER TABLE `items_types_edit_mode` CHANGE `entity_id` `items_types_id` INT UNSIGNED NOT NULL;

ALTER TABLE `experiments_edit_mode` ADD KEY `idx_experiments_edit_mode_all_columns` (`experiments_id`, `locked_by`, `locked_at`);
ALTER TABLE `experiments_templates_edit_mode` ADD KEY `idx_experiments_templates_edit_mode_all_columns` (`experiments_templates_id`, `locked_by`, `locked_at`);
ALTER TABLE `items_edit_mode` ADD KEY `idx_items_edit_mode_all_columns` (`items_id`, `locked_by`, `locked_at`);
ALTER TABLE `items_types_edit_mode` ADD KEY `idx_items_types_edit_mode_all_columns` (`items_types_id`, `locked_by`, `locked_at`);
-- there was never an experiments_templates_edit_mode index
UPDATE config SET conf_value = 172 WHERE conf_name = 'schema';
