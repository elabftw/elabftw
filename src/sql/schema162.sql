-- schema 162
INSERT INTO config (conf_name, conf_value) VALUES ('allow_users_change_identity', '0');
UPDATE config SET conf_value = 162 WHERE conf_name = 'schema';
