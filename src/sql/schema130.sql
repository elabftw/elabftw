-- schema 130
INSERT INTO config (conf_name, conf_value) VALUES ('admins_import_users', '0');
UPDATE config SET conf_value = 130 WHERE conf_name = 'schema';
