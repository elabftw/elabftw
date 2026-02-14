-- schema 201
ALTER TABLE `experiments_categories`
    ADD COLUMN `is_private` TINYINT UNSIGNED NOT NULL DEFAULT 1;

ALTER TABLE `experiments_status`
    ADD COLUMN `is_private` TINYINT UNSIGNED NOT NULL DEFAULT 1;

ALTER TABLE `items_categories`
    ADD COLUMN `is_private` TINYINT UNSIGNED NOT NULL DEFAULT 1;

ALTER TABLE `items_status`
    ADD COLUMN `is_private` TINYINT UNSIGNED NOT NULL DEFAULT 1;
-- these tables should never have existed in the first place
DROP TABLE IF EXISTS `items_types_status`;
DROP TABLE IF EXISTS `experiments_templates_status`;
