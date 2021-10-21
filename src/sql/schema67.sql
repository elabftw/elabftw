-- Schema 67
START TRANSACTION;
    INSERT INTO config (conf_name, conf_value) VALUES ('ts_authority', 'dfn');
    UPDATE `config` SET `conf_name` = 'ts_password' WHERE `conf_name` = 'stamppass';
    ALTER TABLE `teams` ADD `override_tsa` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0';
    ALTER TABLE `teams` ADD `ts_authority` VARCHAR(255) NOT NULL DEFAULT 'dfn';
    ALTER TABLE `teams` CHANGE `stamppass` `ts_password` TEXT NULL DEFAULT NULL;
    UPDATE config SET conf_value = 67 WHERE conf_name = 'schema';
COMMIT;
