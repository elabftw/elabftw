-- Schema 64
START TRANSACTION;
    ALTER TABLE `idps` ADD `email_attr` VARCHAR(255) NOT NULL, ADD `team_attr` VARCHAR(255) NULL DEFAULT NULL, ADD `fname_attr` VARCHAR(255) NULL DEFAULT NULL, ADD `lname_attr` VARCHAR(255) NULL DEFAULT NULL;
    UPDATE `idps` SET idps.email_attr = COALESCE((SELECT config.conf_value FROM config WHERE config.conf_name = 'saml_email'), '');
    UPDATE `idps` SET idps.team_attr = (SELECT config.conf_value FROM config WHERE config.conf_name = 'saml_team');
    UPDATE `idps` SET idps.fname_attr = (SELECT config.conf_value FROM config WHERE config.conf_name = 'saml_firstname');
    UPDATE `idps` SET idps.lname_attr = (SELECT config.conf_value FROM config WHERE config.conf_name = 'saml_lastname');
    DELETE FROM config WHERE conf_name = 'saml_email' OR conf_name = 'saml_firstname' OR conf_name = 'saml_lastname' OR conf_name = 'saml_team';
    DELETE FROM config WHERE conf_name = 'ban_time';
    UPDATE `config` SET `conf_value` = 64 WHERE `conf_name` = 'schema';
COMMIT;
