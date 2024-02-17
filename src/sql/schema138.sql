-- schema 138
-- redo schema 114 again because some users could not use this because it wasn't in Config
DELETE FROM config WHERE conf_name = 'keeex_enabled';
DELETE FROM config WHERE conf_name = 'keeex_host';
DELETE FROM config WHERE conf_name = 'keeex_port';
INSERT INTO config (conf_name, conf_value) VALUES ('keeex_enabled', '0');
INSERT INTO config (conf_name, conf_value) VALUES ('keeex_host', 'keeex');
INSERT INTO config (conf_name, conf_value) VALUES ('keeex_port', '8080');
UPDATE config SET conf_value = 138 WHERE conf_name = 'schema';
