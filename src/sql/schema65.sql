-- Schema 65
START TRANSACTION;
    ALTER TABLE `experiments` ADD `lastchangeby` INT(10) UNSIGNED NULL DEFAULT NULL;
    ALTER TABLE `items` ADD `lastchangeby` INT(10) UNSIGNED NULL DEFAULT NULL;
    ALTER TABLE `experiments_templates` ADD `lastchange` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
    ALTER TABLE `experiments_templates` ADD `lastchangeby` INT(10) UNSIGNED NULL DEFAULT NULL;
    ALTER TABLE `items_types` ADD `lastchange` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
    ALTER TABLE `items_types` ADD `lastchangeby` INT(10) UNSIGNED NULL DEFAULT NULL;
    UPDATE `config` SET `conf_value` = 65 WHERE `conf_name` = 'schema';
COMMIT;
