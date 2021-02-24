-- Schema 56
START TRANSACTION;
    ALTER TABLE `users` ADD `mfa_secret` VARCHAR(32) DEFAULT NULL AFTER `password`;
    INSERT INTO config (conf_name, conf_value) VALUES ('ldap_toggle', '0');
    INSERT INTO config (conf_name, conf_value) VALUES ('ldap_host', 'ldap');
    INSERT INTO config (conf_name, conf_value) VALUES ('ldap_port', '389');
    INSERT INTO config (conf_name, conf_value) VALUES ('ldap_base_dn', '');
    INSERT INTO config (conf_name, conf_value) VALUES ('ldap_username', '');
    INSERT INTO config (conf_name, conf_value) VALUES ('ldap_password', '');
    INSERT INTO config (conf_name, conf_value) VALUES ('ldap_use_tls', '0');
    INSERT INTO config (conf_name, conf_value) VALUES ('ldap_team', 'on');
    INSERT INTO config (conf_name, conf_value) VALUES ('ldap_firstname', 'givenname');
    INSERT INTO config (conf_name, conf_value) VALUES ('ldap_lastname', 'cn');
    INSERT INTO config (conf_name, conf_value) VALUES ('ldap_email', 'mail');
    INSERT INTO config (conf_name, conf_value) VALUES ('ldap_uid_cn', 'cn');
    UPDATE config SET `conf_value` = 'tls' WHERE `conf_value` = 'startssl';
    UPDATE config SET `conf_value` = 'ssl' WHERE `conf_value` = 'tls';
    UPDATE `config` SET `conf_value` = 56 WHERE `conf_name` = 'schema';
COMMIT;
