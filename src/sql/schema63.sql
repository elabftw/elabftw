-- Schema 63
START TRANSACTION;
    CREATE TABLE `authfail` ( `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT, `users_id` INT(10) UNSIGNED NOT NULL , `attempt_time` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, `device_token` VARCHAR(255) NULL DEFAULT NULL, PRIMARY KEY (`id`));
    ALTER TABLE `users` ADD `allow_untrusted` tinyint(1) UNSIGNED NOT NULL DEFAULT '1';
    ALTER TABLE `users` ADD `auth_lock_time` DATETIME NULL DEFAULT NULL;
    CREATE TABLE `lockout_devices` ( `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT, `locked_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, `device_token` VARCHAR(255) NOT NULL, PRIMARY KEY (`id`));
    ALTER TABLE `teams` ADD `force_exp_tpl` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0';
    ALTER TABLE `items_types` CHANGE `name` `name` VARCHAR(255) NOT NULL;
    DROP TABLE `banned_users`;
    UPDATE `config` SET `conf_value` = 63 WHERE `conf_name` = 'schema';
COMMIT;
