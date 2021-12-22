-- Schema 71
START TRANSACTION;
    ALTER TABLE `experiments` CHANGE `date` `date` DATE NOT NULL;
    ALTER TABLE `items` CHANGE `date` `date` DATE NOT NULL;
    ALTER TABLE `experiments_templates` CHANGE `date` `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
    ALTER TABLE `items_types` ADD `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
    UPDATE config SET conf_value = 71 WHERE conf_name = 'schema';
COMMIT;
