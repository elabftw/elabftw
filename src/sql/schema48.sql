-- Schema 48
START TRANSACTION;
    INSERT INTO config (conf_name, conf_value) VALUES ('saml_team_default', NULL);
    UPDATE config SET conf_value = 48 WHERE conf_name = 'schema';
COMMIT;
