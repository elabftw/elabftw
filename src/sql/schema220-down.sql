-- revert schema 220
CALL DropIdx('authfail', 'idx_authfail_users_attempt_time');
CALL DropIdx('authfail', 'idx_authfail_device_attempt_time');
UPDATE config SET conf_value = 219 WHERE conf_name = 'schema';
