-- revert schema 133
DROP TABLE IF EXISTS `audit_logs`;
UPDATE config SET conf_value = 133 WHERE conf_name = 'schema';
