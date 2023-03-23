-- schema 114
INSERT INTO config (conf_name, conf_value) VALUES ('keeex_enabled', '0');
INSERT INTO config (conf_name, conf_value) VALUES ('keeex_host', 'keeex');
INSERT INTO config (conf_name, conf_value) VALUES ('keeex_port', '8080');
UPDATE config SET conf_value = 114 WHERE conf_name = 'schema';
