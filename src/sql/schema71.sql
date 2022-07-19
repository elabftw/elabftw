-- Schema 71
START TRANSACTION;
    UPDATE `experiments` SET `date` = NOW() WHERE `datetime` < '2000-01-01 00:00:00';
    UPDATE `items` SET `date` = NOW() WHERE `datetime` < '2000-01-01 00:00:00';
    ALTER TABLE `experiments` CHANGE `date` `date` DATE NOT NULL;
    ALTER TABLE `items` CHANGE `date` `date` DATE NOT NULL;
    ALTER TABLE `experiments_templates` CHANGE `date` `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
    ALTER TABLE `items_types` ADD `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
    UPDATE config SET conf_value = 71 WHERE conf_name = 'schema';
COMMIT;
