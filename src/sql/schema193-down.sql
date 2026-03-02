-- revert schema 193
INSERT INTO config (conf_name, conf_value) VALUES ('open_science', '0');
INSERT INTO config (conf_name, conf_value) VALUES ('open_team', NULL);
UPDATE config SET conf_value = 192 WHERE conf_name = 'schema';
