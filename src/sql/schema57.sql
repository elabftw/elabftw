-- Schema 57
START TRANSACTION;
    ALTER TABLE `users` ADD `display_mode` VARCHAR(2) NOT NULL DEFAULT 'it';
    ALTER TABLE `users` CHANGE `password` `password` VARCHAR(255) NULL DEFAULT NULL;
    ALTER TABLE `users` CHANGE `salt` `salt` VARCHAR(255) NULL DEFAULT NULL;
    ALTER TABLE `users` ADD `password_hash` VARCHAR(255) NULL DEFAULT NULL AFTER `password`;
    INSERT INTO config (conf_name, conf_value) VALUES ('devmode', '0');
    INSERT INTO config (conf_name, conf_value) VALUES ('autologout_time', '0');
    INSERT INTO config (conf_name, conf_value) VALUES ('min_delta_revisions', '100');
    ALTER TABLE `items` ADD `elabid` VARCHAR(255) NOT NULL;
    ALTER TABLE `experiments` CHANGE `datetime` `datetime` TIMESTAMP NULL DEFAULT NULL;
    ALTER TABLE `experiments` ADD `metadata` JSON NULL DEFAULT NULL;
    ALTER TABLE `items` ADD `metadata` JSON NULL DEFAULT NULL;
    ALTER TABLE `experiments_templates` ADD `metadata` JSON NULL DEFAULT NULL;
    ALTER TABLE `items_types` ADD `metadata` JSON NULL DEFAULT NULL;
    ALTER TABLE `items_types` ADD `canread` VARCHAR(255) NOT NULL DEFAULT 'team';
    ALTER TABLE `items_types` ADD`canwrite` VARCHAR(255) NOT NULL DEFAULT 'team';
    UPDATE `config` SET `conf_value` = 57 WHERE `conf_name` = 'schema';
COMMIT;
