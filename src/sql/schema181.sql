-- schema 181
INSERT INTO config (conf_name, conf_value) VALUES ('allow_permission_team', '1');
INSERT INTO config (conf_name, conf_value) VALUES ('allow_permission_user', '1');
INSERT INTO config (conf_name, conf_value) VALUES ('allow_permission_full', '1');
INSERT INTO config (conf_name, conf_value) VALUES ('allow_permission_organization', '1');
UPDATE config SET conf_name = 'allow_permission_useronly' WHERE conf_name = 'allow_useronly';
