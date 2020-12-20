-- Schema 57
START TRANSACTION;
    ALTER TABLE `users` ADD `display_mode` VARCHAR(2) NOT NULL DEFAULT 'it';
    UPDATE `config` SET `conf_value` = 57 WHERE `conf_name` = 'schema';
COMMIT;
