-- revert schema 219
DROP TABLE IF EXISTS `mfa_rate_limits`;
CALL DropIdx('authfail', 'idx_authfail_users_attempt_time');
CALL DropIdx('authfail', 'idx_authfail_device_attempt_time');
UPDATE config SET conf_value = 218 WHERE conf_name = 'schema';
