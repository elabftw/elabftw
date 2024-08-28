-- revert schema 165
DELETE FROM config WHERE conf_name = 'local_auth_enabled';
UPDATE config SET conf_value = 164 WHERE conf_name = 'schema';
