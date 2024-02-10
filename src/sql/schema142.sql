-- schema 142
INSERT INTO config (conf_name, conf_value) VALUES ('min_password_length', '12');
INSERT INTO config (conf_name, conf_value) VALUES ('password_complexity_requirement', '0');
INSERT INTO config (conf_name, conf_value) VALUES ('max_password_age_days', '3650');
ALTER TABLE `users` ADD `password_modified_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
UPDATE config SET conf_value = 142 WHERE conf_name = 'schema';
