START TRANSACTION;
    ALTER TABLE `users` ADD `use_ove` tinyint(1) NOT NULL DEFAULT '1' AFTER `use_markdown`;
    UPDATE `config` SET `conf_value` = 60 WHERE `conf_name` = 'schema';
COMMIT;
