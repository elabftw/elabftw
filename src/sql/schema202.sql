-- schema 202
INSERT INTO config (conf_name, conf_value) VALUES ('ldap_sync_teams', 0);
INSERT INTO config (conf_name, conf_value)
    SELECT 'ldap_team_create', conf_value
    FROM config
    WHERE conf_name = 'saml_team_create';
