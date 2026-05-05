-- schema 209
CREATE TABLE IF NOT EXISTS `storage_units_history` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `storage_unit_id` INT UNSIGNED NOT NULL,
    `old_parent_id` INT UNSIGNED NULL,
    `new_parent_id` INT UNSIGNED NULL,
    `users_id` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY (`storage_unit_id`, `created_at`),
    FOREIGN KEY (`storage_unit_id`) REFERENCES `storage_units`(`id`) ON DELETE CASCADE
);
UPDATE config SET conf_value = 209 WHERE conf_name = 'schema';
