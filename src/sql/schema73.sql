-- Schema 73
START TRANSACTION;
    ALTER TABLE `experiments` ADD `state` int(10) UNSIGNED NOT NULL DEFAULT 1;
    ALTER TABLE `items` ADD `state` int(10) UNSIGNED NOT NULL DEFAULT 1;
    ALTER TABLE `experiments_templates` ADD `state` int(10) UNSIGNED NOT NULL DEFAULT 1;
    ALTER TABLE `items_types` ADD `state` int(10) UNSIGNED NOT NULL DEFAULT 1;
    CREATE INDEX `idx_experiments_state` on `experiments` (`state`);
    CREATE INDEX `idx_experiments_templates_state` on `experiments_templates` (`state`);
    CREATE INDEX `idx_items_state` on `items` (`state`);
    CREATE INDEX `idx_items_types_state` on `items_types` (`state`);
    UPDATE config SET conf_value = 73 WHERE conf_name = 'schema';
COMMIT;
