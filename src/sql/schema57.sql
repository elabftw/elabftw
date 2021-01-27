-- Schema 57
START TRANSACTION;
    ALTER TABLE `users` ADD `display_mode` VARCHAR(2) NOT NULL DEFAULT 'it';
    ALTER TABLE `users` CHANGE `password` `password` VARCHAR(255) NULL DEFAULT NULL;
    ALTER TABLE `users` CHANGE `salt` `salt` VARCHAR(255) NULL DEFAULT NULL;
    ALTER TABLE `users` ADD `password_hash` VARCHAR(255) NULL DEFAULT NULL AFTER `password`;
    INSERT INTO config (conf_name, conf_value) VALUES ('devmode', '0');
    UPDATE `config` SET `conf_value` = 57 WHERE `conf_name` = 'schema';
COMMIT;
