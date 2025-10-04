-- schema 184
ALTER TABLE `users`
    ADD COLUMN `can_manage_compounds` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    ADD COLUMN `can_manage_inventory_locations` TINYINT UNSIGNED NOT NULL DEFAULT 0;
