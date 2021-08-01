-- Schema 62
START TRANSACTION;
    ALTER TABLE `groups` CHANGE `is_admin` `is_admin` TINYINT(1) UNSIGNED NOT NULL;
    ALTER TABLE `groups` CHANGE `can_lock` `can_lock` TINYINT(1) UNSIGNED NOT NULL;
    DELETE FROM `config` WHERE `conf_name` = 'ldap_uid_cn';
    UPDATE `config` SET `conf_value` = 62 WHERE `conf_name` = 'schema';
COMMIT;
