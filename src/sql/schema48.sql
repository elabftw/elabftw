-- Schema 48
START TRANSACTION;
    INSERT INTO config (conf_name, conf_value) VALUES ('saml_team_default', NULL);
    INSERT INTO config (conf_name, conf_value) VALUES ('saml_team_create', '1');
    INSERT INTO config (conf_name, conf_value) VALUES ('saml_toggle', '0');
    UPDATE config SET conf_value = 48 WHERE conf_name = 'schema';
COMMIT;
