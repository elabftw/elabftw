-- schema 112
INSERT INTO config (conf_name, conf_value) VALUES ('enforce_mfa', '0');
UPDATE config SET conf_value = 112 WHERE conf_name = 'schema';
