-- revert schema 188
CALL DropIdx('experiments_templates_steps', 'idx_experiments_templates_steps_is_immutable');
CALL DropIdx('items_types_steps', 'idx_items_types_steps_is_immutable');
CALL DropIdx('items_steps', 'idx_items_steps_is_immutable');
CALL DropIdx('experiments_steps', 'idx_experiments_steps_is_immutable');

ALTER TABLE `experiments_templates_steps`
    DROP COLUMN `is_immutable`;

ALTER TABLE `items_types_steps`
    DROP COLUMN `is_immutable`;

ALTER TABLE `items_steps`
    DROP COLUMN `is_immutable`;

ALTER TABLE `experiments_steps`
    DROP COLUMN `is_immutable`;

UPDATE config SET conf_value = 187 WHERE conf_name = 'schema';
