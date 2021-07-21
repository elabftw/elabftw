-- Schema 62
START TRANSACTION;
    ALTER TABLE `groups` CHANGE `is_admin` `is_admin` TINYINT(1) UNSIGNED NOT NULL;
    ALTER TABLE `groups` CHANGE `can_lock` `can_lock` TINYINT(1) UNSIGNED NOT NULL;
    UPDATE `config` SET `conf_value` = 62 WHERE `conf_name` = 'schema';
COMMIT;
