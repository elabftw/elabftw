-- Schema 63
START TRANSACTION;
    ALTER TABLE `teams` ADD `force_exp_tpl` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0';
    ALTER TABLE `items_types` CHANGE `name` `name` VARCHAR(255) NOT NULL;
    UPDATE `config` SET `conf_value` = 63 WHERE `conf_name` = 'schema';
COMMIT;
