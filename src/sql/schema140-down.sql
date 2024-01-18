-- revert schema 140
DELETE FROM config WHERE conf_name = 'emit_audit_logs';
UPDATE config SET conf_value = 139 WHERE conf_name = 'schema';
