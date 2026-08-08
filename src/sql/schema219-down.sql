-- revert schema 219
DROP TABLE IF EXISTS `mfa_rate_limits`;
UPDATE config SET conf_value = 218 WHERE conf_name = 'schema';
