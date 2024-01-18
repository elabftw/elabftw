-- schema 140
INSERT INTO config (conf_name, conf_value) VALUES ('emit_audit_logs', '0');
UPDATE config SET conf_value = 140 WHERE conf_name = 'schema';
