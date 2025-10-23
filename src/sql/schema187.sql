-- schema 187
ALTER TABLE `experiments_templates_steps`
    ADD COLUMN `is_immutable` tinyint UNSIGNED NOT NULL DEFAULT 0,
    ADD INDEX `idx_experiments_templates_steps_is_immutable` (`is_immutable`);

ALTER TABLE `items_types_steps`
    ADD COLUMN `is_immutable` tinyint UNSIGNED NOT NULL DEFAULT 0,
    ADD INDEX `idx_items_types_steps_is_immutable` (`is_immutable`);

ALTER TABLE `items_steps`
    ADD COLUMN `is_immutable` tinyint UNSIGNED NOT NULL DEFAULT 0,
    ADD INDEX `idx_items_steps_is_immutable` (`is_immutable`);

ALTER TABLE `experiments_steps`
    ADD COLUMN `is_immutable` tinyint UNSIGNED NOT NULL DEFAULT 0,
    ADD INDEX `idx_experiments_steps_is_immutable` (`is_immutable`);
