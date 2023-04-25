-- schema 115
INSERT INTO config (conf_name, conf_value) VALUES ('admins_create_users_remote_dir', '0');
INSERT INTO config (conf_name, conf_value) VALUES ('remote_dir_service', NULL);
INSERT INTO config (conf_name, conf_value) VALUES ('remote_dir_config', NULL);
ALTER TABLE `users` ADD `orgid` VARCHAR(255) NULL DEFAULT NULL;
UPDATE config SET conf_value = 115 WHERE conf_name = 'schema';
