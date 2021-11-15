-- Schema 67
START TRANSACTION;
    INSERT INTO config (conf_name, conf_value) VALUES ('ts_authority', 'dfn');
    UPDATE `config` SET `conf_name` = 'ts_login' WHERE `conf_name` = 'stamplogin';
    UPDATE `config` SET `conf_name` = 'ts_password' WHERE `conf_name` = 'stamppass';
    UPDATE `config` SET `conf_name` = 'ts_url' WHERE `conf_name` = 'stampprovider';
    UPDATE `config` SET `conf_name` = 'ts_cert' WHERE `conf_name` = 'stampcert';
    UPDATE `config` SET `conf_name` = 'ts_hash' WHERE `conf_name` = 'stamphash';
    UPDATE `config` SET `conf_name` = 'ts_share' WHERE `conf_name` = 'stampshare';
    ALTER TABLE `teams` ADD `ts_override` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0';
    ALTER TABLE `teams` ADD `ts_authority` VARCHAR(255) NOT NULL DEFAULT 'dfn';
    ALTER TABLE `teams` CHANGE `stamplogin` `ts_login` VARCHAR(255) NULL DEFAULT NULL;
    UPDATE `teams` SET `stamppass` = NULL;
    ALTER TABLE `teams` CHANGE `stamppass` `ts_password` VARCHAR(255) NULL DEFAULT NULL;
    ALTER TABLE `teams` CHANGE `stampprovider` `ts_url` VARCHAR(255) NULL DEFAULT NULL;
    ALTER TABLE `teams` CHANGE `stampcert` `ts_cert` VARCHAR(255) NULL DEFAULT NULL;
    ALTER TABLE `teams` CHANGE `stamphash` `ts_hash` VARCHAR(255) NULL DEFAULT NULL;
    UPDATE config SET conf_value = 67 WHERE conf_name = 'schema';
COMMIT;
