-- schema 209
CREATE TABLE IF NOT EXISTS `storage_units_history` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `storage_unit_id` INT UNSIGNED NOT NULL,
    `old_parent_id` INT UNSIGNED NULL,
    `new_parent_id` INT UNSIGNED NULL,
    `users_id` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY (`storage_unit_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
UPDATE config SET conf_value = 209 WHERE conf_name = 'schema';
