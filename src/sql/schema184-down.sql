-- revert schema 184
ALTER TABLE `users`
    DROP COLUMN `can_manage_compounds`,
    DROP COLUMN `can_manage_inventory_locations`;
UPDATE config SET conf_value = 183 WHERE conf_name = 'schema';
