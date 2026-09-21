-- revert schema 225
ALTER TABLE `experiments_steps`
    DROP FOREIGN KEY `fk_experiments_steps_group_id`,
    DROP INDEX `idx_experiments_steps_group_id`,
    DROP COLUMN `group_id`;
ALTER TABLE `items_steps`
    DROP FOREIGN KEY `fk_items_steps_group_id`,
    DROP INDEX `idx_items_steps_group_id`,
    DROP COLUMN `group_id`;
ALTER TABLE `experiments_templates_steps`
    DROP FOREIGN KEY `fk_experiments_templates_steps_group_id`,
    DROP INDEX `idx_experiments_templates_steps_group_id`,
    DROP COLUMN `group_id`;
ALTER TABLE `items_types_steps`
    DROP FOREIGN KEY `fk_items_types_steps_group_id`,
    DROP INDEX `idx_items_types_steps_group_id`,
    DROP COLUMN `group_id`;

DROP TABLE `experiments_step_groups`;
DROP TABLE `items_step_groups`;
DROP TABLE `experiments_templates_step_groups`;
DROP TABLE `items_types_step_groups`;

UPDATE config SET conf_value = 224 WHERE conf_name = 'schema';
