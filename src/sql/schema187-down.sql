-- revert schema 187
ALTER TABLE `experiments_templates_steps`
    DROP INDEX `idx_experiments_templates_steps_is_immutable`,
    DROP COLUMN `is_immutable`;

ALTER TABLE `items_types_steps`
    DROP INDEX `idx_items_types_steps_is_immutable`,
    DROP COLUMN `is_immutable`;

ALTER TABLE `items_steps`
    DROP INDEX `idx_items_steps_is_immutable`,
    DROP COLUMN `is_immutable`;

ALTER TABLE `experiments_steps`
    DROP INDEX `idx_experiments_steps_is_immutable`,
    DROP COLUMN `is_immutable`;

UPDATE config SET conf_value = 186 WHERE conf_name = 'schema';
