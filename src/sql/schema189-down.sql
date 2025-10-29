-- revert schema 189
ALTER TABLE `experiments_templates_steps`
    DROP COLUMN `is_immutable`;

ALTER TABLE `items_types_steps`
    DROP COLUMN `is_immutable`;

ALTER TABLE `items_steps`
    DROP COLUMN `is_immutable`;

ALTER TABLE `experiments_steps`
    DROP COLUMN `is_immutable`;

UPDATE config SET conf_value = 188 WHERE conf_name = 'schema';
