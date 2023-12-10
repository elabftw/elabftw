-- revert schema 136
DROP TABLE IF EXISTS audit_logs;
UPDATE config SET conf_value = 135 WHERE conf_name = 'schema';
