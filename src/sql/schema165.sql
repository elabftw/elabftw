-- schema 165
INSERT INTO config (conf_name, conf_value) VALUES ('local_auth_enabled', '1');
UPDATE config SET conf_value = 165 WHERE conf_name = 'schema';
