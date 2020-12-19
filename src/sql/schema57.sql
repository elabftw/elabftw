-- Schema 57
START TRANSACTION;
    ALTER TABLE `users` ADD `default_role` ENUM('user', 'admin') NOT NULL DEFAULT 'user';
    UPDATE `config` SET `conf_value` = 57 WHERE `conf_name` = 'schema';
COMMIT;
