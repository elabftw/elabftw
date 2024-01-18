-- schema 140
INSERT INTO config (conf_name, conf_value) VALUES ('emit_audit_logs', '0');
INSERT INTO config (conf_name, conf_value) VALUES ('chat_url', 'https://gitter.im/elabftw/elabftw');
UPDATE config SET conf_value = 140 WHERE conf_name = 'schema';
