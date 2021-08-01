-- Schema 63
START TRANSACTION;
    ALTER TABLE `teams` ADD `force_exp_tpl` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0';
    UPDATE `config` SET `conf_value` = 63 WHERE `conf_name` = 'schema';
COMMIT;
