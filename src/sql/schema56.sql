-- Schema 56
START TRANSACTION;
    ALTER TABLE `users` ADD `mfa_secret` VARCHAR(32) DEFAULT NULL AFTER `password`;

    UPDATE `config` SET `conf_value` = 56 WHERE `conf_name` = 'schema';
COMMIT;
