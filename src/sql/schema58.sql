START TRANSACTION;
    ALTER TABLE `users` ADD `append_pdfs` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `inc_files_pdf`;
    UPDATE `config` SET `conf_value` = 58 WHERE `conf_name` = 'schema';
COMMIT;
