-- revert schema 133
DROP TABLE IF EXISTS `audit_logs`;
UPDATE config SET conf_value = 132 WHERE conf_name = 'schema';
