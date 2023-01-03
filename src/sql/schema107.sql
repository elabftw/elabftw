-- schema 107
INSERT INTO config (conf_name, conf_value) VALUES ('ldap_search_attr', 'mail');
UPDATE config SET conf_value = 107 WHERE conf_name = 'schema';
