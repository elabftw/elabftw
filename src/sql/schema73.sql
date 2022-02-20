-- Schema 73
START TRANSACTION;
    ALTER TABLE `experiments` ADD `state` int(10) UNSIGNED NOT NULL DEFAULT 1;
    ALTER TABLE `items` ADD `state` int(10) UNSIGNED NOT NULL DEFAULT 1;
    ALTER TABLE `experiments_templates` ADD `state` int(10) UNSIGNED NOT NULL DEFAULT 1;
    ALTER TABLE `items_types` ADD `state` int(10) UNSIGNED NOT NULL DEFAULT 1;
    UPDATE config SET conf_value = 73 WHERE conf_name = 'schema';
COMMIT;
