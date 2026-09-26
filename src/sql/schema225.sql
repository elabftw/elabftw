-- schema 225
-- Keep one step-group table per entity type so groups can use real foreign keys
-- to the same parent tables as the existing step tables.
CREATE TABLE `experiments_step_groups` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `item_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `ordering` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_experiments_step_groups_item_id` (`item_id`),
    CONSTRAINT `fk_experiments_step_groups_item_id` FOREIGN KEY (`item_id`) REFERENCES `experiments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `items_step_groups` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `item_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `ordering` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_items_step_groups_item_id` (`item_id`),
    CONSTRAINT `fk_items_step_groups_item_id` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `experiments_templates_step_groups` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `item_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `ordering` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_experiments_templates_step_groups_item_id` (`item_id`),
    CONSTRAINT `fk_experiments_templates_step_groups_item_id` FOREIGN KEY (`item_id`) REFERENCES `experiments_templates` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `items_types_step_groups` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `item_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `ordering` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_items_types_step_groups_item_id` (`item_id`),
    CONSTRAINT `fk_items_types_step_groups_item_id` FOREIGN KEY (`item_id`) REFERENCES `items_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- A NULL group_id represents Default group. ON DELETE SET NULL ensures that
-- deleting a group never deletes its steps. They go to Default group
ALTER TABLE `experiments_steps`
    ADD COLUMN `group_id` INT UNSIGNED NULL DEFAULT NULL AFTER `item_id`,
    ADD KEY `idx_experiments_steps_group_id` (`group_id`),
    ADD CONSTRAINT `fk_experiments_steps_group_id` FOREIGN KEY (`group_id`) REFERENCES `experiments_step_groups` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `items_steps`
    ADD COLUMN `group_id` INT UNSIGNED NULL DEFAULT NULL AFTER `item_id`,
    ADD KEY `idx_items_steps_group_id` (`group_id`),
    ADD CONSTRAINT `fk_items_steps_group_id` FOREIGN KEY (`group_id`) REFERENCES `items_step_groups` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `experiments_templates_steps`
    ADD COLUMN `group_id` INT UNSIGNED NULL DEFAULT NULL AFTER `item_id`,
    ADD KEY `idx_experiments_templates_steps_group_id` (`group_id`),
    ADD CONSTRAINT `fk_experiments_templates_steps_group_id` FOREIGN KEY (`group_id`) REFERENCES `experiments_templates_step_groups` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `items_types_steps`
    ADD COLUMN `group_id` INT UNSIGNED NULL DEFAULT NULL AFTER `item_id`,
    ADD KEY `idx_items_types_steps_group_id` (`group_id`),
    ADD CONSTRAINT `fk_items_types_steps_group_id` FOREIGN KEY (`group_id`) REFERENCES `items_types_step_groups` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
