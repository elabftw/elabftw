-- schema 189
ALTER TABLE `experiments_templates_steps`
    ADD COLUMN `is_immutable` tinyint UNSIGNED NOT NULL DEFAULT 0;

ALTER TABLE `items_types_steps`
    ADD COLUMN `is_immutable` tinyint UNSIGNED NOT NULL DEFAULT 0;

ALTER TABLE `items_steps`
    ADD COLUMN `is_immutable` tinyint UNSIGNED NOT NULL DEFAULT 0;

ALTER TABLE `experiments_steps`
    ADD COLUMN `is_immutable` tinyint UNSIGNED NOT NULL DEFAULT 0;
