-- schema 178
-- add local_login_hidden_only_sysadmin and local_login_only_sysadmin config keys
INSERT INTO config (conf_name, conf_value) VALUES ('local_login_hidden_only_sysadmin', '0');
INSERT INTO config (conf_name, conf_value) VALUES ('local_login_only_sysadmin', '0');
