-- Schema 61
START TRANSACTION;
    ALTER TABLE `users` ADD `use_ove` tinyint(1) NOT NULL DEFAULT '1' AFTER `use_markdown`;
    UPDATE `config` SET `conf_value` = 61 WHERE `conf_name` = 'schema';
COMMIT;
