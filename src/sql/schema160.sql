-- schema 160
INSERT INTO `config` (conf_name, conf_value) VALUES ('ldap_scheme', 'ldap');
UPDATE config SET conf_value = 160 WHERE conf_name = 'schema';
