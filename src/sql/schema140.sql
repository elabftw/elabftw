-- schema 140
INSERT INTO config (conf_name, conf_value) VALUES ('emit_audit_logs', '0');
INSERT INTO config (conf_name, conf_value) VALUES ('chat_url', 'https://gitter.im/elabftw/elabftw');
ALTER TABLE `users` ADD `last_seen_version` INT UNSIGNED NOT NULL DEFAULT 40900;
UPDATE config SET conf_value = 140 WHERE conf_name = 'schema';
